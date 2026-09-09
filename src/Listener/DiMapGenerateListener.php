<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Listener;

use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionInterface;
use Hyperf\Di\Definition\FactoryDefinition;
use Hyperf\Di\Definition\ObjectDefinition;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\class_basename;

/**
 * 收集 DI 容器映射关系，服务启动后生成 di-map.json。
 *
 * path 字段统一转为相对 BASE_PATH 的格式（不在 BASE_PATH 下才保留绝对路径），
 * 供 IDE 插件按应用根解析，规避 PHP 跑在 WSL 而 IDE 在 Windows 的路径差。
 *
 * 注意：collect() 依赖 Hyperf\Di\Container 的内部属性（definitionSource / resolvedEntries），
 * 升级 hyperf/di 版本时需验证这两个属性名是否变更。
 */
class DiMapGenerateListener implements ListenerInterface
{
    public function __construct(
        private StdoutLoggerInterface $logger,
        private SwaggerConfig $swaggerConfig,
        private ContainerInterface $container,
    ) {}

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            AfterWorkerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event): void
    {
        /** @var AfterWorkerStart|MainCoroutineServerStart $event */
        $isCoroutineServer = $event instanceof MainCoroutineServerStart;
        if (! $isCoroutineServer && $event->workerId !== 0) {
            return;
        }
        try {
            $this->generate();
        } catch (\Throwable $e) {
            $this->logger->error('Generate Di Map file failed: ' . $e->getMessage());
        }
    }

    /**
     * 生成 di-map.json 到 api_docs 配置的 output_dir。
     */
    public function generate(): void
    {
        if (! $this->swaggerConfig->isEnable() || ! $outputDir = $this->swaggerConfig->getOutputDir()) {
            return;
        }

        $path = rtrim($outputDir, '/\\') . '/di-map.json';
        $payload = [
                       'base_path' => BASE_PATH,
                       'generated_at' => date('c'),
                   ] + $this->collect();
        // 原子写：避免 IDE 插件读到写了一半的文件
        $tmpPath = $path . '.tmp';
        file_put_contents($tmpPath, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
        rename($tmpPath, $path);
        $this->logger->debug('Generate Di Map file: ' . $path);
    }

    /**
     * 收集 DI 容器中的所有映射关系（只读，不执行工厂闭包）。
     * resolved 为调用时刻的快照，之后运行时 set() 的条目不包含在内。
     *
     * @return array{definitions: array<string, array{type: string, class: ?string, path: ?string}>, resolved: array<string, array{type: string, class: ?string, path: ?string}>}
     */
    public function collect(): array
    {
        if (! $this->container instanceof Container) {
            return ['definitions' => [], 'resolved' => []];
        }

        $definitions = [];
        foreach ($this->readProperty($this->container, 'definitionSource')->getDefinitions() as $name => $definition) {
            // getDefinition() 的 autowire 缓存会把不存在的类写入 null，跳过
            if (! $definition instanceof DefinitionInterface) {
                continue;
            }
            $definitions[$name] = $this->formatDefinition($definition);
        }

        $resolved = [];
        foreach ($this->readProperty($this->container, 'resolvedEntries') as $name => $value) {
            $class = is_object($value) ? $value::class : null;
            $resolved[$name] = [
                'type' => $class ? 'object' : gettype($value),
                'class' => $class,
                'path' => $class ? $this->getClassPath($class) : null,
            ];
        }

        return [
            'definitions' => $this->filterSelfMapped($definitions),
            'resolved' => $this->filterSelfMapped($resolved),
        ];
    }

    /**
     * 过滤掉 name 与目标类相同的自映射条目，并按 name 排序。
     * 工厂定义除外：即使产出类与 name 相同，其 path 指向工厂文件，有定位价值。
     *
     * @param array<string, array{type: string, class: ?string, path: ?string}> $map
     * @return array<string, array{type: string, class: ?string, path: ?string}>
     */
    private function filterSelfMapped(array $map): array
    {
        $map = array_filter($map, fn (array $d, string $name): bool => $d['class'] !== $name || $d['type'] === 'factory', ARRAY_FILTER_USE_BOTH);
        ksort($map);
        return $map;
    }

    /**
     * @return array{type: string, class: ?string, path: ?string}
     */
    private function formatDefinition(DefinitionInterface $definition): array
    {
        if ($definition instanceof ObjectDefinition) {
            $class = $definition->getClassName();
            return [
                'type' => 'object',
                'class' => $class,
                'path' => $this->getClassPath($class),
            ];
        }
        if ($definition instanceof FactoryDefinition) {
            $factory = $definition->getFactory();
            return [
                'type' => 'factory',
                'class' => $this->getFactoryClass($factory),
                'path' => $this->getFactoryPath($factory),
            ];
        }
        return [
            'type' => class_basename($definition),
            'class' => null,
            'path' => null,
        ];
    }

    /**
     * 工厂定义位置：闭包记录定义文件，类形式（类名、[类, 方法]、类::方法、__invoke 对象）记录类文件路径。
     */
    private function getFactoryPath(callable|string $factory): ?string
    {
        if ($factory instanceof \Closure) {
            $file = (new \ReflectionFunction($factory))->getFileName();
            return $this->localizePath($file ?: null);
        }
        if (is_array($factory)) {
            // [类名或对象, 方法名]
            return $this->getClassPath(is_object($factory[0]) ? $factory[0]::class : $factory[0]);
        }
        if (is_string($factory)) {
            // 类名 或 类名::方法
            return $this->getClassPath(explode('::', $factory)[0]);
        }
        // 实现了 __invoke 的工厂对象
        return $this->getClassPath($factory::class);
    }

    /**
     * 推断工厂产出的类：反射工厂 callable 声明的返回类型，是类（非标量）则记录，否则为 null。
     * 覆盖闭包、[类, 方法]、类::方法、带 __invoke 的类名或对象；未声明返回类型时为 null。
     */
    private function getFactoryClass(callable|string $factory): ?string
    {
        try {
            $callable = match (true) {
                $factory instanceof \Closure => new \ReflectionFunction($factory),
                is_array($factory) => new \ReflectionMethod($factory[0], $factory[1]),
                is_string($factory) && str_contains($factory, '::') => new \ReflectionMethod(...explode('::', $factory, 2)),
                is_string($factory) && method_exists($factory, '__invoke') => new \ReflectionMethod($factory, '__invoke'),
                is_object($factory) => new \ReflectionMethod($factory, '__invoke'),
                default => null,
            };
        } catch (\ReflectionException) {
            return null;
        }
        $type = $callable?->getReturnType();
        if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }
        $name = $type->getName();
        $lower = strtolower($name);
        if (! in_array($lower, ['self', 'static', 'parent'], true)) {
            return $name;
        }
        // self/static/parent 需结合声明类解析
        if (! $callable instanceof \ReflectionMethod) {
            return null;
        }
        if ($lower === 'parent') {
            $parent = $callable->getDeclaringClass()->getParentClass();
            return $parent ? $parent->getName() : null;
        }
        return $callable->getDeclaringClass()->getName();
    }

    private function getClassPath(string $class): ?string
    {
        if (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class)) {
            return null;
        }
        $file = (new \ReflectionClass($class))->getFileName();
        if (! $file) {
            return null;
        }
        // AOP 代理类的反射路径指向 runtime/container/proxy/*.proxy.php（代理保留原类名）；
        // 运行时 composer classmap 已被代理路径覆盖（ClassLoader::init 的 addClassMap），
        // 只能从磁盘上的 autoload 文件反查原始文件
        if (str_contains(str_replace('\\', '/', $file), '/runtime/container/proxy/')) {
            $file = $this->findOriginalClassFile($class) ?? $file;
        }
        return $this->localizePath($file);
    }

    /**
     * 路径转相对 BASE_PATH（IDE 插件按应用根解析，规避 WSL/Windows 路径差）；
     * 不在 BASE_PATH 下（如宿主编译路径、外部挂载）保留绝对路径。
     */
    private function localizePath(?string $file): ?string
    {
        if ($file === null) {
            return null;
        }
        $prefix = BASE_PATH . '/';
        return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
    }

    /**
     * 从磁盘上的 composer autoload 文件反查类的原始文件（绕开运行时被代理覆盖的 classmap）。
     */
    private function findOriginalClassFile(string $class): ?string
    {
        $classMapFile = BASE_PATH . '/vendor/composer/autoload_classmap.php';
        if (is_file($classMapFile)) {
            $classMap = include $classMapFile;
            if (isset($classMap[$class])) {
                return $classMap[$class];
            }
        }
        $psr4File = BASE_PATH . '/vendor/composer/autoload_psr4.php';
        if (is_file($psr4File)) {
            foreach ((array) include $psr4File as $prefix => $dirs) {
                if (! str_starts_with($class, (string) $prefix)) {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($class, strlen((string) $prefix))) . '.php';
                foreach ((array) $dirs as $dir) {
                    if (is_file($file = rtrim((string) $dir, '/') . '/' . $relative)) {
                        return $file;
                    }
                }
            }
        }
        return null;
    }

    private function readProperty(object $object, string $property): mixed
    {
        return (new \ReflectionProperty($object, $property))->getValue($object);
    }
}
