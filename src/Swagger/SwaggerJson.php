<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Collect\MainCollect;
use Hyperf\ApiDocs\Collect\RouteCollect;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Utils\ApplicationContext;
use HyperfExample\ApiDocs\Controller\DemoController;
use HyperfExample\ApiDocs\Controller\TestController;
use JetBrains\PhpStorm\Deprecated;
use Psr\Container\ContainerInterface;

class SwaggerJson
{
    public mixed $config;

    public StdoutLoggerInterface $stdoutLogger;

    public string $serverName;

    public int $index = 0;

    public array $classMethodArray = [];

    public array $routeArray = [];

    private static array $className;

    private static array $simpleClassName;

    private ContainerInterface $container;

    public function __construct(string $serverName)
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        $this->serverName = $serverName;
    }

    /**
     * 增加一条路由.
     */
    public function addPath(string $className, string $methodName, string $route, string $methods)
    {
        $position = $this->getMethodNamePosition($className, $methodName);
        $classAnnotation = ApiAnnotation::classMetadata($className);
        /** @var Api $apiControllerAnnotation */
        $apiControllerAnnotation = $classAnnotation[Api::class] ?? new Api();
        if ($apiControllerAnnotation->hidden) {
            return;
        }

        $apiHeaderControllerAnnotation = isset($classAnnotation[ApiHeader::class]) ? $classAnnotation[ApiHeader::class]->toAnnotations() : [];
        // AutoController Validation POST
        $autoControllerAnnotation = $classAnnotation[AutoController::class] ?? null;
        if ($autoControllerAnnotation && $methods != 'POST') {
            return;
        }
        $methodAnnotations = AnnotationCollector::getClassMethodAnnotation($className, $methodName);
        $apiOperation = $methodAnnotations[ApiOperation::class] ?? new ApiOperation();
        if ($apiOperation->hidden) {
            return;
        }

        $apiHeaderArr = isset($methodAnnotations[ApiHeader::class]) ? $methodAnnotations[ApiHeader::class]->toAnnotations() : [];
        $apiHeaderArr = array_merge($apiHeaderControllerAnnotation, $apiHeaderArr);
        $apiFormDataArr = isset($methodAnnotations[ApiFormData::class]) ? $methodAnnotations[ApiFormData::class]->toAnnotations() : [];
        $apiResponseArr = isset($methodAnnotations[ApiResponse::class]) ? $methodAnnotations[ApiResponse::class]->toAnnotations() : [];
        $isDeprecated = isset($methodAnnotations[Deprecated::class]);

        $simpleClassName = static::getSimpleClassName($className);
        if (is_array($apiControllerAnnotation->tags)) {
            $tags = $apiControllerAnnotation->tags;
        } elseif (! empty($apiControllerAnnotation->tags) && is_string($apiControllerAnnotation->tags)) {
            $tags = [$apiControllerAnnotation->tags];
        } else {
            $tags = [$simpleClassName];
        }

        foreach ($tags as $tag) {
            MainCollect::setTags($tag, [
                'name' => $tag,
                'position' => $apiControllerAnnotation->position,
                'description' => $apiControllerAnnotation->description ?: $simpleClassName,
            ]);
        }

        $method = strtolower($methods);
        $makeParameters = new GenerateParameters($route, $method, $className, $methodName, $apiHeaderArr, $apiFormDataArr);
        $makeResponses = new GenerateResponses($className, $methodName, $apiResponseArr, $this->config->get('api_docs'));

        [$parameters ,$consumeType] = $makeParameters->generate();

        $routeCollect = new RouteCollect();
        $routeCollect->route = $route;
        $routeCollect->requestMethod = strtolower($methods);
        $routeCollect->simpleClassName = $simpleClassName;
        $routeCollect->position = $position;
        $routeCollect->tags = $tags;
        $routeCollect->summary = $apiOperation->summary ?? '';
        $routeCollect->description = $apiOperation->description ?? '';
        $routeCollect->deprecated = $isDeprecated;
        $routeCollect->operationId = implode('', array_map('ucfirst', explode('/', $route))) . $methods;
        $routeCollect->consumeTypes = $consumeType ? [$consumeType] : [];
        $routeCollect->produces = ['*/*'];
        $routeCollect->parameters = $parameters;
        $routeCollect->responses = $makeResponses->generate();
        $routeCollect->isSecurity = $apiOperation->isSecurity;
        MainCollect::setRoutes($routeCollect);
    }

    /**
     * 获得简单类名.
     */
    public static function getSimpleClassName(string $className): string
    {
        $className = '\\' . trim($className, '\\');
        if (isset(self::$className[$className])) {
            return self::$className[$className];
        }
        $simpleClassName = substr($className, strrpos($className, '\\') + 1);
        if (isset(self::$simpleClassName[$simpleClassName])) {
            $simpleClassName .= '-' . (++self::$simpleClassName[$simpleClassName]);
        } else {
            self::$simpleClassName[$simpleClassName] = 0;
        }
        self::$className[$className] = $simpleClassName;
        return $simpleClassName;
    }

    /**
     * 保存.
     */
    public function save(array $swagger): string
    {
        $outputDir = $this->config->get('api_docs.output_dir');
        if (! $outputDir) {
            $this->stdoutLogger->error('/config/autoload/api_docs.php need set output_dir');
            return '';
        }
        $outputFile = $outputDir . '/' . $this->serverName . '.json';
        $this->putFile($outputFile, json_encode($swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->stdoutLogger->debug("swagger generate {$outputFile} success!");
        return $outputFile;
    }

    /**
     * 获取方法在类中的位置.
     */
    private function getMethodNamePosition(string $className, string $methodName): int
    {
        $methodArray = $this->makeMethodIndex($className);
        return $methodArray[$methodName] ?? 0;
    }

    /**
     * 设置位置并获取类位置数组.
     */
    private function makeMethodIndex(string $className): array
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
     * put file.
     */
    private function putFile(string $file, string $content): void
    {
        $pathInfo = pathinfo($file);
        if (! empty($pathInfo['dirname'])) {
            if (file_exists($pathInfo['dirname']) === false) {
                if (mkdir($pathInfo['dirname'], 0755, true) === false) {
                    return;
                }
            }
        }
        file_put_contents($file, $content);
    }
}
