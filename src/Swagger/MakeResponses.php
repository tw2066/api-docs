<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class MakeResponses
{
    private string $className;

    private string $methodName;

    private Common $common;

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
        $this->common = new Common();
    }

    public function make(): array
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($this->className, $this->methodName);
        $returnTypeClassName = $definition->getName();
        $code = $this->config['responses_code'] ?? 200;
        $resp[$code] = $this->makeSchema($returnTypeClassName);
        $resp[$code]['description'] = 'OK';
        $globalResp = $this->makeGlobalResp();
        $AnnotationResp = $this->makeAnnotationResp();
        return $AnnotationResp + $resp + $globalResp;
    }

    private function makeSchema($returnTypeClassName): array
    {
        $schema = [];
        if ($this->common->isSimpleType($returnTypeClassName)) {
            $type = $this->common->type2SwaggerType($returnTypeClassName);
            if ($type == 'array') {
                $schema['schema']['items'] = (object) [];
            }
            if ($type == 'object') {
                $schema['schema']['items'] = (object) [];
            }
            $schema['schema']['type'] = $type;
        } elseif ($this->container->has($returnTypeClassName)) {
            $this->common->class2schema($returnTypeClassName);
            $schema['schema']['$ref'] = $this->common->getDefinitions($returnTypeClassName);
        }
        return $schema;
    }

    private function makeGlobalResp(): array
    {
        $resp = [];
        foreach ($this->config['responses'] as $code => $value) {
            isset($value['className']) && $resp[$code] = $this->makeSchema($value['className']);
            $resp[$code]['description'] = $value['description'];
        }
        return $resp;
    }

    private function makeAnnotationResp(): array
    {
        $resp = [];
        /** @var ApiResponse $apiResponse */
        foreach ($this->apiResponseArr as $apiResponse) {
            if ($apiResponse->className) {
                $scanAnnotation = $this->container->get(ScanAnnotation::class);
                $scanAnnotation->scanClass($apiResponse->className);
                $this->makeSchema($apiResponse->className);
                if (! empty($apiResponse->type)) {
                    $schema['schema']['type'] = $apiResponse->type;
                    $schema['schema']['items']['$ref'] = $this->makeSchema($apiResponse->className)['schema']['$ref'];
                    $resp[$apiResponse->code] = $schema;
                } else {
                    $resp[$apiResponse->code] = $this->makeSchema($apiResponse->className);
                }
            }
            $resp[$apiResponse->code]['description'] = $apiResponse->description;
        }
        return $resp;
    }
}
