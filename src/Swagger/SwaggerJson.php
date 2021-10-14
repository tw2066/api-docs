<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class SwaggerJson
{
    public $config;

    public static $swagger = [];

    public $stdoutLogger;

    public string $serverName;

    public int $index = 0;

    public array $classMethodArray = [];

    public array $routeArray = [];

    private static array $className;

    private static array $simpleClassName;

    /**
     * @var MethodDefinitionCollectorInterface|mixed
     */
    private $methodDefinitionCollector;

    private ContainerInterface $container;

    public function __construct(string $serverName)
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        self::$swagger = $this->config->get('apidocs.swagger');
        $this->serverName = $serverName;
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->securityKey();
    }

    public static function getSimpleClassName($className)
    {
        $className = '\\' . trim($className, '\\');
        if (isset(self::$className[$className])) {
            return self::$className[$className];
        }
        $simpleClassName = substr($className, strrpos($className, '\\') + 1);
        if (isset(self::$simpleClassName[$simpleClassName])) {
            $simpleClassName .= ++self::$simpleClassName[$simpleClassName];
        } else {
            self::$simpleClassName[$simpleClassName] = 0;
        }
        self::$className[$className] = $simpleClassName;
        return $simpleClassName;
    }

    /**
     * 增加路由路径.
     * @param $methods
     * @param $route
     * @param $className
     * @param $methodName
     */
    public function addPath($className, $methodName, $route, $methods)
    {
        //获取当前方法位置
        $position = $this->getMethodNamePosition($className, $methodName, $route);
        $classAnnotation = ApiAnnotation::classMetadata($className);
        /** @var Api $apiControllerAnnotation */
        $apiControllerAnnotation = $classAnnotation[Api::class] ?? new Api();
        /** @var Api $apiHeaderControllerAnnotation */
        $apiHeaderControllerAnnotation = $classAnnotation[ApiHeader::class] ?? null;
        //AutoController Validation POST
        $autoControllerAnnotation = $classAnnotation[AutoController::class] ?? null;
        if ($autoControllerAnnotation && $methods != 'POST') {
            return;
        }
        $methodAnnotations = ApiAnnotation::methodArray($className, $methodName);
        $apiHeaderArr = $apiHeaderControllerAnnotation ? [$apiHeaderControllerAnnotation] : [];
        $apiOperation = new ApiOperation();
        $apiFormDataArr = [];
        $apiResponseArr = [];
        foreach ($methodAnnotations as $option) {
            /* @var ApiOperation $apiOperationAnnotation */
            if ($option instanceof ApiOperation) {
                $apiOperation = $option;
            }
            if ($option instanceof ApiHeader) {
                $apiHeaderArr[] = $option;
            }
            if ($option instanceof ApiFormData) {
                $apiFormDataArr[] = $option;
            }
            if ($option instanceof ApiResponse) {
                $apiResponseArr[] = $option;
            }
        }
        $simpleClassName = $this->getSimpleClassName($className);
        if (is_array($apiControllerAnnotation->tags)) {
            $tags = $apiControllerAnnotation->tags;
        } elseif (!empty($apiControllerAnnotation->tags) && is_string($apiControllerAnnotation->tags)) {
            $tags = [$apiControllerAnnotation->tags];
        } else {
            $tags = [$simpleClassName];
        }

        foreach ($tags as $tag) {
            self::$swagger['tags'][$tag] = [
                'name' => $tag,
                'position' => $apiControllerAnnotation->position,
                'description' => $apiControllerAnnotation->description ?: $simpleClassName,
            ];
        }

        $method = strtolower($methods);
        $makeParameters = new MakeParameters($route, $method, $className, $methodName, $apiHeaderArr, $apiFormDataArr);
        $makeResponses = new MakeResponses($className, $methodName, $apiResponseArr, $this->config->get('apidocs'));
        self::$swagger['paths'][$route]['position'] = $position;
        self::$swagger['paths'][$route][$method] = [
            'tags' => $tags,
            'summary' => $apiOperation->summary ?? '',
            'description' => $apiOperation->description ?? '',
            'operationId' => implode('', array_map('ucfirst', explode('/', $route))) . $methods,
            'parameters' => $makeParameters->make(),
            'produces' => [
                'application/json',
            ],
            'responses' => $makeResponses->make(),
            'security' => $this->securityMethod(),
        ];
    }

    /**
     * 保存到文件.
     * @return string|void
     */
    public function save()
    {
        self::$swagger = $this->sort(self::$swagger);
        $outputDir = $this->config->get('apidocs.output_dir');
        if (!$outputDir) {
            $this->stdoutLogger->error('/config/autoload/apidocs.php need set output_dir');
            return;
        }
        $outputFile = $outputDir . '/' . $this->serverName . '.json';
        $this->putFile($outputFile, json_encode(self::$swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        self::$swagger = [];
        $this->stdoutLogger->debug('Generate swagger.json success!');
        return $outputFile;
    }

    private function getMethodNamePosition($className, $methodName)
    {
        $methodArray = $this->makeMethodIndex($className);
        return $methodArray[$methodName] ?? 0;
    }

    private function makeMethodIndex($className)
    {
        if (isset($this->classMethodArray[$className])) {
            return $this->classMethodArray[$className];
        }
        $methodArray = ApiAnnotation::methodMetadata($className);
        foreach ($methodArray as $k => $item) {
            $methodArray[$k] = $this->index;
            ++$this->index;
        }
        $this->classMethodArray[$className] = $methodArray;
        return $methodArray;
    }

    /**
     * security.
     */
    private function securityKey()
    {
        $securityKeyArr = $this->config->get('apidocs.security_api_key', []);
        if (empty($securityKeyArr)) {
            return;
        }
        $securityDefinitions = [];
        foreach ($securityKeyArr as $value) {
            $securityDefinitions[$value] = [
                'type' => 'apiKey',
                'name' => $value,
                'in' => 'header',
            ];
        }
        self::$swagger['securityDefinitions'] = $securityDefinitions;
    }

    /**
     * 授权key.
     * @return array|void
     */
    private function securityMethod()
    {
        $securityKeyArr = $this->config->get('apidocs.security_api_key', []);
        if (empty($securityKeyArr)) {
            return;
        }
        $security = [];
        foreach ($securityKeyArr as $value) {
            $security[] = [
                $value => [],
            ];
        }
        return $security;
    }

    /**
     * json文件写入.
     * @return false|int
     */
    private function putFile(string $file, string $content)
    {
        $pathInfo = pathinfo($file);
        if (!empty($pathInfo['dirname'])) {
            if (file_exists($pathInfo['dirname']) === false) {
                if (mkdir($pathInfo['dirname'], 0644, true) === false) {
                    return false;
                }
            }
        }
        return file_put_contents($file, $content);
    }

    /**
     * 排序处理.
     */
    private function sort(array $data): array
    {
        $data['tags'] = collect($data['tags'] ?? [])
            ->sortByDesc('position')
            ->map(function ($item) {
                return collect($item)->except('position');
            })
            ->values()
            ->toArray();
        $data['paths'] = collect($data['paths'] ?? [])
            ->sortBy('position')
            ->map(function ($item) {
                return collect($item)->except('position');
            })
            ->toArray();
        return $data;
    }
}
