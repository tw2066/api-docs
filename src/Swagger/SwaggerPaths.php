<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Annotation\ApiSecurity;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\MultipleAnnotationInterface;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\HttpServer\Annotation\AutoController;
use JetBrains\PhpStorm\Deprecated;
use OpenApi\Annotations\Operation;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\PathItem;
use function Hyperf\Support\make;

class SwaggerPaths
{
    public int $index = 0;

    public array $classMethodArray = [];

    public function __construct(
        public string $serverName,
        public ConfigInterface $config,
        public StdoutLoggerInterface $stdoutLogger,
        private SwaggerOpenApi $swaggerOpenApi,
        private SwaggerCommon $common,
    ) {
    }

    /**
     * 增加一条路由.
     */
    public function addPath(string $className, string $methodName, string $route, string $methods): void
    {
        $pathItem = new PathItem();
        // 获取类中方法的位置
        $position = $this->getMethodNamePosition($className, $methodName);
        $classAnnotation = ApiAnnotation::classMetadata($className);
        /** @var Api $apiControllerAnnotation */
        $apiControllerAnnotation = $classAnnotation[Api::class] ?? new Api();
        if ($apiControllerAnnotation->hidden) {
            return;
        }

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

        $apiHeaderControllerAnnotation = isset($classAnnotation[ApiHeader::class]) ? $classAnnotation[ApiHeader::class]->toAnnotations() : [];
        $apiHeaderArr = isset($methodAnnotations[ApiHeader::class]) ? $methodAnnotations[ApiHeader::class]->toAnnotations() : [];
        $apiHeaderArr = array_merge($apiHeaderControllerAnnotation, $apiHeaderArr);

        $apiFormDataArr = isset($methodAnnotations[ApiFormData::class]) ? $methodAnnotations[ApiFormData::class]->toAnnotations() : [];
        $apiResponseArr = isset($methodAnnotations[ApiResponse::class]) ? $methodAnnotations[ApiResponse::class]->toAnnotations() : [];

        $isDeprecated = false;
        class_exists(Deprecated::class) && $isDeprecated = isset($methodAnnotations[Deprecated::class]);

        $simpleClassName = $this->common->getSimpleClassName($className);
        if (is_array($apiControllerAnnotation->tags)) {
            $tags = $apiControllerAnnotation->tags;
        } elseif (! empty($apiControllerAnnotation->tags) && is_string($apiControllerAnnotation->tags)) {
            $tags = [$apiControllerAnnotation->tags];
        } else {
            $tags = [$simpleClassName];
        }

        foreach ($tags as $tag) {
            $oaTag = new OA\Tag();
            $oaTag->name = $tag;
            $oaTag->description = $apiControllerAnnotation->description ?: $simpleClassName;
            $this->swaggerOpenApi->setTags($tag, $apiControllerAnnotation->position, $oaTag);
        }

        $method = strtolower($methods);
        /** @var GenerateParameters $generateParameters */
        $generateParameters = make(GenerateParameters::class, [$className, $methodName, $apiHeaderArr, $apiFormDataArr]);
        /** @var GenerateResponses $generateResponses */
        $generateResponses = make(GenerateResponses::class, [$className, $methodName, $apiResponseArr]);
        $parameters = $generateParameters->generate();
        $pathItem->path = $route;

        $OAClass = 'OpenApi\Attributes\\' . ucfirst($method);
        /** @var Operation $operation */
        $operation = new $OAClass();
        $operation->path = $route;
        $operation->tags = $tags;
        $operation->summary = $apiOperation->summary;
        $operation->description = $apiOperation->description;
        $operation->operationId = implode('', array_map('ucfirst', explode('/', $route))) . $methods;

        $isDeprecated && $operation->deprecated = true;

        $parameters['requestBody'] && $operation->requestBody = $parameters['requestBody'];
        $parameters['parameter'] && $operation->parameters = $parameters['parameter'];

        $operation->responses = $generateResponses->generate();

        // 安全验证
        $securityArr = $this->getSecurity($classAnnotation, $methodAnnotations);
        if ($securityArr !== false) {
            $operation->security = $securityArr;
        }

        $pathItem->{$method} = $operation;
        // 将$pathItem insert
        $this->swaggerOpenApi->getQueuePaths()->insert([$pathItem, $method], 0 - $position);
    }

    /**
     * 获取安全认证
     * @param mixed $classAnnotation
     * @param mixed $methodAnnotations
     */
    protected function getSecurity($classAnnotation, $methodAnnotations): array|false
    {
        $apiOperation = $methodAnnotations[ApiOperation::class] ?? new ApiOperation();
        if (! $apiOperation->security) {
            return [];
        }

        // 方法设置了
        $isMethodSetApiSecurity = $this->isSetApiSecurity($methodAnnotations);
        if ($isMethodSetApiSecurity) {
            return $this->setSecurity($methodAnnotations);
        }

        // 类上设置了
        $isClassSetApiSecurity = $this->isSetApiSecurity($classAnnotation);
        if ($isClassSetApiSecurity) {
            return $this->setSecurity($classAnnotation);
        }

        return false;
    }

    private function isSetApiSecurity(?array $annotations): bool
    {
        foreach ($annotations ?? [] as $item) {
            if ($item instanceof MultipleAnnotationInterface) {
                $toAnnotations = $item->toAnnotations();
                foreach ($toAnnotations as $annotation) {
                    if ($annotation instanceof ApiSecurity) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 设置安全验证
     */
    private function setSecurity(?array $annotations): array
    {
        $result = [];
        foreach ($annotations ?? [] as $item) {
            if ($item instanceof MultipleAnnotationInterface) {
                $toAnnotations = $item->toAnnotations();
                foreach ($toAnnotations as $annotation) {
                    if ($annotation instanceof ApiSecurity) {
                        // 存在需要设置的security
                        if (! empty($annotation->name)) {
                            $result[][$annotation->name] = $annotation->value;
                        }
                    }
                }
            }
        }
        return $result;
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
}
