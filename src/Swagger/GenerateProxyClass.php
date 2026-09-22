<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiVariable;
use Hyperf\ApiDocs\Ast\ResponseVisitor;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\DTO\DtoConfig;
use Hyperf\Support\Composer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use ReflectionClass;
use SplFileInfo;

use function Hyperf\Support\make;

class GenerateProxyClass
{
    protected ?array $apiVariableClassArr = null;

    protected array $proxyClassArr = [];

    public function __construct(
        protected SwaggerConfig $swaggerConfig,
        protected SwaggerCommon $swaggerCommon,
        protected DtoConfig $dtoConfig,
    ) {
        $proxyDir = $this->swaggerConfig->getProxyDir();
        if (file_exists($proxyDir) === false) {
            if (mkdir($proxyDir, 0755, true) === false) {
                throw new ApiDocsException("Failed to create a directory : {$proxyDir}");
            }
        }
    }

    public function getApiVariableClass(string $newClassname)
    {
        $newClassname = trim($newClassname, '\\');
        if ($this->apiVariableClassArr === null) {
            $arr = [];
            $classes = AnnotationCollector::getPropertiesByAnnotation(ApiVariable::class);
            foreach ($classes as $class) {
                $classname = $class['class'];
                $arr[$classname][] = $class['property'];
            }
            $this->apiVariableClassArr = $arr;
        }
        return $this->apiVariableClassArr[$newClassname] ?? [];
    }

    /**
     * 生成代理类.
     */
    public function generate(object $obj): string
    {
        $classname = $obj::class;
        $properties = $this->getApiVariableClass($classname);
        if (empty($properties)) {
            return $classname;
        }

        $propertyValues = [];
        foreach ($properties as $property) {
            // 获取变量值
            $propertyValues[$property] = $obj->{$property};
        }
        return $this->generateProxy($classname, $propertyValues);
    }

    /**
     * 通过属性类型映射生成代理类(无需实例化).
     * @param array<string, mixed> $types 属性名 => 类名字符串/PhpType/实例/单元素数组
     */
    public function generateByTypes(string $classname, array $types): string
    {
        $classname = trim($classname, '\\');
        $properties = $this->getApiVariableClass($classname);
        if (empty($types) || empty($properties)) {
            return $classname;
        }
        foreach ($types as $property => $type) {
            if (! in_array($property, $properties, true)) {
                throw new ApiDocsException("{$classname}: \${$property} is not an ApiVariable property");
            }
        }
        // 未指定的可变属性按mixed处理(与实例写法中属性值为null一致)
        $propertyValues = [];
        foreach ($properties as $property) {
            $propertyValues[$property] = $types[$property] ?? null;
        }
        return $this->generateProxy($classname, $propertyValues);
    }

    /**
     * 获取代理类对应的源类.
     */
    public function getSourceClassname(string $proxyClassname): ?string
    {
        return $this->proxyClassArr[$proxyClassname] ?? null;
    }

    protected function generateProxy(string $classname, array $propertyValues): string
    {
        $propertyArr = [];
        foreach ($propertyValues as $property => $propertyValue) {
            $type = $this->swaggerCommon->getPhpType($propertyValue);
            if (is_object($propertyValue) && $type != '\stdClass') {
                $propertyClassname = $type;
                if ($this->getApiVariableClass($propertyClassname)) {
                    $propertyClassname = '\\' . $this->generate($propertyValue);
                }
                $type = $propertyClassname;
            }
            $propertyArr[$property] = $type;
            if (is_array($propertyValue) && count($propertyValue) > 0) {
                $arrayType = $this->swaggerCommon->getPhpType($propertyValue[0]);
                if (is_object($propertyValue[0]) && $propertyValue[0]::class != '\stdClass') {
                    $propertyClassname = $arrayType;
                    if ($this->getApiVariableClass($propertyClassname)) {
                        $propertyClassname = '\\' . $this->generate($propertyValue[0]);
                    }
                    $arrayType = $propertyClassname;
                }
                $propertyArr[$property] = [$arrayType];
            }
            if (is_array($propertyValue) && count($propertyValue) == 0) {
                $propertyArr[$property] = 'array';
            }
        }

        $ref = new ReflectionClass($classname);
        $file = new SplFileInfo($ref->getFileName());
        $realPath = $file->getRealPath();
        [$generateNamespaceClassName, $content] = $this->phpParser($classname, $realPath, $propertyArr);

        if (! isset($this->proxyClassArr[$generateNamespaceClassName])) {
            $this->putContents($generateNamespaceClassName, $content);
            $this->proxyClassArr[$generateNamespaceClassName] = $classname;
        }

        return $generateNamespaceClassName;
    }

    protected function putContents($generateNamespaceClassName, $content): void
    {
        $outputDir = $this->swaggerConfig->getProxyDir();
        $generateClassName = str_replace('\\', '_', $generateNamespaceClassName);
        $filename = $outputDir . $generateClassName . '.dto.proxy.php';
        // 代理文件已存在且开启扫描缓存时跳过写入(视为构建期产物); 否则生成
        if (! ($this->dtoConfig->isScanCacheable() && file_exists($filename))) {
            file_put_contents($filename, $content);
        }
        $classLoader = Composer::getLoader();
        $classLoader->addClassMap([$generateNamespaceClassName => $filename]);
    }

    protected function phpParser(string $classname, $filePath, $propertyArr): array
    {
        $code = file_get_contents($filePath);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        $simpleClassName = $this->swaggerCommon->getSimpleClassName($classname);
        $generateClassName = $simpleClassName;
        foreach ($propertyArr as $type) {
            if (is_array($type)) {
                $generateClassName .= 'Array';
                $type = $type[0];
            }
            $type = $this->swaggerCommon->getSimpleClassName($type);
            $generateClassName .= $type;
        }
        $fullGenerateClassName = 'ApiDocs\Proxy\\' . $generateClassName;
        if (isset($this->proxyClassArr[$fullGenerateClassName])) {
            return [$fullGenerateClassName, ''];
        }

        $traverser = new NodeTraverser();
        $resVisitor = make(ResponseVisitor::class, [$generateClassName, $propertyArr]);
        $traverser->addVisitor($resVisitor);
        $ast = $traverser->traverse($ast);

        $prettyPrinter = new PrettyPrinter\Standard();
        $content = $prettyPrinter->prettyPrintFile($ast);
        return [$fullGenerateClassName, $content];
    }
}
