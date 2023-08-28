<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Collection\Arr;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerInterface;

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
    ) {
    }

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

        $annotationResp && $arr = Arr::merge($arr, $annotationResp);
        $globalResp && $arr = Arr::merge($arr, $globalResp);

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

    protected function getContent(string|array|object $returnTypeClassName): array
    {
        // 获取全局类
        $globalReturnResponsesClass = $this->swaggerConfig->getGlobalReturnResponsesClass();
        if ($globalReturnResponsesClass) {
            $returnTypeClassName = \Hyperf\Support\make($globalReturnResponsesClass, [$returnTypeClassName]);
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
        $arr = [];
        $mediaType = new OA\MediaType();
        $mediaTypeStr = 'text/plain';
        // 简单类型
        if ($this->common->isSimpleType($returnTypeClassName)) {
            $schema = new OA\Schema();
            $schema->type = $this->common->getSwaggerType($returnTypeClassName);
            // 数组
            if ($isArray) {
                $mediaTypeStr = 'application/json';
                $schema->type = 'array';
                $items = new OA\Items();
                $swaggerType = $this->common->getSwaggerType($returnTypeClassName);
                $items->type = $swaggerType == 'array' ? 'null' : $swaggerType;
                $schema->items = $items;
            }
            $mediaType->schema = $schema;
        } elseif ($this->container->has($returnTypeClassName)) {
            $mediaTypeStr = 'application/json';
            $mediaType->schema = $this->getJsonContent($returnTypeClassName, $isArray);
        } else {
            //            $schema = new OA\Schema();
            //            $schema->type = 'null';
            //            $mediaType->schema = $schema;
            // 其他类型数据 eg:mixed
            return [];
        }

        $arr[$mediaTypeStr] = $mediaType;
        $mediaType->mediaType = $mediaTypeStr;
        return $arr;
    }

    /**
     * 获取返回类型的JsonContent.
     */
    protected function getJsonContent(string $returnTypeClassName, bool $isArray): OA\JsonContent
    {
        $jsonContent = new OA\JsonContent();
        $this->swaggerComponents->generateSchemas($returnTypeClassName);

        if ($isArray) {
            $jsonContent->type = 'array';
            $items = new OA\Items();
            $items->ref = $this->common->getComponentsName($returnTypeClassName);
            $jsonContent->items = $items;
        } else {
            $jsonContent->ref = $this->common->getComponentsName($returnTypeClassName);
        }

        return $jsonContent;
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
            $content = $this->getContent($returnType);
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
