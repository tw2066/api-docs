<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Collection\Arr;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

class GenerateResponses
{
    public function __construct(
        protected string $className,
        protected string $methodName,
        protected array $apiResponseArr,
        protected SwaggerConfig $swaggerConfig,
        protected MethodDefinitionCollectorInterface $methodDefinitionCollector,
        protected ContainerInterface $container,
        protected SwaggerComponents $swaggerComponents,
        protected SwaggerCommon $common,
        protected GenerateProxyClass $genericProxyClass,
    ) {}

    /**
     * 生成Response.
     */
    public function generate(): array
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($this->className, $this->methodName);
        $returnTypeClassName = $definition->getName();
        // 全局
        $globalResp = $this->getGlobalResp();
        // 注解
        $annotationResp = $this->getAnnotationResp();
        $arr = [];

        $code = $this->swaggerConfig->getResponsesCode();
        $response = new OA\Response();
        $response->response = $code;
        $response->description = 'successful operation';
        $content = $this->getContent($returnTypeClassName);
        $content && $response->content = $content;
        $arr[$code] = $response;

        // 优先级：方法级 ApiResponse 注解 > 全局 responses 配置
        $globalResp && $arr = Arr::merge($arr, $globalResp);
        $annotationResp && $arr = Arr::merge($arr, $annotationResp);

        return array_values($arr);
    }

    //    protected function getReturnJsonContent(string $returnTypeClassName, bool $isArray = false): array
    //    {
    //        $arr = [];
    //        $mediaType = new OA\MediaType();
    //        $mediaTypeStr = 'application/json';
    //        $mediaType->schema = $this->getJsonContent($returnTypeClassName, $isArray);
    //        $arr[$mediaTypeStr] = $mediaType;
    //        $mediaType->mediaType = $mediaTypeStr;
    //        return $arr;
    //    }

    protected function getContent(array|object|string $returnTypeClassName, array $types = []): array
    {
        // 获取全局类
        $globalReturnResponsesClass = $this->swaggerConfig->getGlobalReturnResponsesClass();
        if ($globalReturnResponsesClass) {
            $returnTypeClassName = make($globalReturnResponsesClass, [$returnTypeClassName]);
        }
        // 类名 + 属性类型映射(无需实例化)
        if ($types !== [] && is_string($returnTypeClassName) && $this->genericProxyClass->getApiVariableClass($returnTypeClassName)) {
            $returnTypeClassName = $this->genericProxyClass->generateByTypes($returnTypeClassName, $types);
        }
        // 判断对象
        if (is_object($returnTypeClassName)) {
            // 生成代理类
            if ($this->genericProxyClass->getApiVariableClass($returnTypeClassName::class)) {
                $returnTypeClassName = $this->genericProxyClass->generate($returnTypeClassName);
            } else {
                $returnTypeClassName = $returnTypeClassName::class;
            }
        }

        $isArray = is_array($returnTypeClassName);
        if ($isArray) {
            $returnTypeClassName = $returnTypeClassName[0] ?? null;
            $returnTypeClassName = is_object($returnTypeClassName) ? $returnTypeClassName::class : $returnTypeClassName;
        }
        $returnTypeClassName == 'array' && $isArray = true;
        $schemaType = $returnTypeClassName;
        if ($isArray && $schemaType !== 'array') {
            $schemaType = ($schemaType ?? 'mixed') . '[]';
        }

        $mediaType = new OA\MediaType();
        if ($isArray) {
            $mediaType->mediaType = 'application/json';
            $mediaType->schema = $this->swaggerComponents->createSchema(type: $schemaType);
        } elseif ($this->common->isSimpleType($returnTypeClassName)) {
            $mediaType->mediaType = 'text/plain';
            $mediaType->schema = $this->swaggerComponents->createSchema(type: $returnTypeClassName);
        } elseif (is_string($returnTypeClassName) && (
            $this->container->has($returnTypeClassName)
            || class_exists($returnTypeClassName)
            || interface_exists($returnTypeClassName)
        )) {
            $mediaType->mediaType = 'application/json';
            $mediaType->schema = $this->swaggerComponents->createSchema(type: $returnTypeClassName);
        } else {
            return [];
        }

        return [$mediaType->mediaType => $mediaType];
    }

    /**
     * 获得全局Response.
     */
    protected function getGlobalResp(): array
    {
        $resp = [];
        foreach ($this->swaggerConfig->getResponses() as $value) {
            $apiResponse = new ApiResponse();
            $apiResponse->response = $value['response'] ?? null;
            $apiResponse->description = $value['description'] ?? null;
            ! empty($value['returnType']) && $apiResponse->returnType = $value['returnType'];
            ! empty($value['types']) && $apiResponse->types = $value['types'];
            $resp[$apiResponse->response] = $this->getOAResp($apiResponse);
        }
        return $resp;
    }

    protected function getOAResp(ApiResponse $apiResponse): OA\Response
    {
        $response = new OA\Response();
        $response->response = $apiResponse->response;
        $response->description = $apiResponse->description;
        if (! empty($apiResponse->returnType)) {
            $returnType = $apiResponse->returnType;
            $content = $this->getContent($returnType, $apiResponse->types);
            $content && $response->content = $content;
        }
        return $response;
    }

    /**
     * 获取注解上的Response.
     * @return OA\Response[]
     */
    protected function getAnnotationResp(): array
    {
        $resp = [];
        /** @var ApiResponse $apiResponse */
        foreach ($this->apiResponseArr as $apiResponse) {
            $resp[$apiResponse->response] = $this->getOAResp($apiResponse);
        }
        return $resp;
    }
}
