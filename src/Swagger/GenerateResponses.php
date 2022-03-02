<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Collect\ResponseInfo;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Scan\Property;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

class GenerateResponses
{
    use SwaggerCommon;

    private string $className;

    private string $methodName;

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    private array $config;

    private array $apiResponseArr;

    public function __construct(string $className, string $methodName, array $apiResponseArr, array $config)
    {
        $this->container = ApplicationContext::getContainer();
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->className = $className;
        $this->methodName = $methodName;
        $this->config = $config;
        $this->apiResponseArr = $apiResponseArr;
    }

    /**
     * 生成Response.
     */
    public function generate(): array
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($this->className, $this->methodName);
        $returnTypeClassName = $definition->getName();
        $code = $this->config['responses_code'] ?? 200;
        $globalResp = $this->getGlobalResp();
        $annotationResp = $this->getAnnotationResp();
        $arr = [];
        $responseInfo = $this->getResponseInfo($returnTypeClassName);
        $responseInfo->code = $code;
        $responseInfo->description = 'OK';
        $arr[] = $responseInfo;
        $annotationResp && $arr = Arr::merge($arr, $annotationResp);
        $globalResp && $arr = Arr::merge($arr, $globalResp);
        return $arr;
    }

    /**
     * 获取返回类型的Response.
     */
    protected function getResponseInfo(string $returnTypeClassName): ResponseInfo
    {
        $responseInfo = new ResponseInfo();
        $property = new Property();
        $property->isSimpleType = true;
        if ($this->isSimpleType($returnTypeClassName)) {
            $property->phpSimpleType = $returnTypeClassName;
        } elseif (class_exists($returnTypeClassName)) {
            $property->isSimpleType = false;
            $property->className = $returnTypeClassName;
        }
        $responseInfo->property = $property;
        return $responseInfo;
    }

    /**
     * 获得全局Response.
     */
    protected function getGlobalResp(): array
    {
        $resp = [];
        foreach ($this->config['responses'] as $code => $value) {
            $apiResponse = new ApiResponse();
            $apiResponse->code = (string) $code;
            $apiResponse->description = $value['description'] ?? null;
            ! empty($value['type']) && $apiResponse->type = $value['type'];
            ! empty($value['className']) && $apiResponse->className = $value['className'];
            $resp[] = ResponseInfo::form($apiResponse);
        }
        return $resp;
    }

    /**
     * 获取注解上的Response.
     * @return ResponseInfo[]
     */
    protected function getAnnotationResp(): array
    {
        $resp = [];
        /** @var ApiResponse $apiResponse */
        foreach ($this->apiResponseArr as $apiResponse) {
            $resp[] = ResponseInfo::form($apiResponse);
        }
        return $resp;
    }
}
