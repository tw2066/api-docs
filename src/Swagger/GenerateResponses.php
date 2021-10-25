<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class GenerateResponses
{
    private string $className;

    private string $methodName;

    private SwaggerCommon $common;

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
        $this->common = new SwaggerCommon();
    }

    public function generate(): array
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($this->className, $this->methodName);
        $returnTypeClassName = $definition->getName();
        $code = $this->config['responses_code'] ?? 200;
        $resp[$code] = $this->getSchema($returnTypeClassName);
        $resp[$code]['description'] = 'OK';
        $globalResp = $this->getGlobalResp();
        $AnnotationResp = $this->getAnnotationResp();
        return $AnnotationResp + $resp + $globalResp;
    }

    private function getSchema($returnTypeClassName): array
    {
        $schema = [];
        if ($this->common->isSimpleType($returnTypeClassName)) {
            $type = $this->common->getType2SwaggerType($returnTypeClassName);
            if ($type == 'array') {
                $schema['schema']['items'] = (object) [];
            }
            if ($type == 'object') {
                $schema['schema']['items'] = (object) [];
            }
            $schema['schema']['type'] = $type;
        } elseif ($this->container->has($returnTypeClassName)) {
            $this->common->generateClass2schema($returnTypeClassName);
            $schema['schema']['$ref'] = $this->common->getDefinitions($returnTypeClassName);
        }
        return $schema;
    }

    private function getGlobalResp(): array
    {
        $resp = [];
        foreach ($this->config['responses'] as $code => $value) {
            isset($value['className']) && $resp[$code] = $this->getSchema($value['className']);
            $resp[$code]['description'] = $value['description'];
        }
        return $resp;
    }

    private function getAnnotationResp(): array
    {
        $resp = [];
        /** @var ApiResponse $apiResponse */
        foreach ($this->apiResponseArr as $apiResponse) {
            if ($apiResponse->className) {
                $scanAnnotation = $this->container->get(ScanAnnotation::class);
                $scanAnnotation->scanClass($apiResponse->className);
                $this->getSchema($apiResponse->className);
                if (! empty($apiResponse->type)) {
                    $schema['schema']['type'] = $apiResponse->type;
                    $schema['schema']['items']['$ref'] = $this->getSchema($apiResponse->className)['schema']['$ref'];
                    $resp[$apiResponse->code] = $schema;
                } else {
                    $resp[$apiResponse->code] = $this->getSchema($apiResponse->className);
                }
            }
            $resp[$apiResponse->code]['description'] = $apiResponse->description;
        }
        return $resp;
    }
}
