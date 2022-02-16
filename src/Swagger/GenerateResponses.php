<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use stdClass;

class GenerateResponses
{
    private string $className;

    private string $methodName;

    private SwaggerCommon $common;

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    private array $config;

    private array $apiResponseArr;

    private int $version;

    public function __construct(string $className, string $methodName, array $apiResponseArr, array $config, int $version)
    {
        $this->container = ApplicationContext::getContainer();
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->className = $className;
        $this->methodName = $methodName;
        $this->config = $config;
        $this->apiResponseArr = $apiResponseArr;
        $this->common = new SwaggerCommon($version);
        $this->version = $version;
    }

    protected function getMethodDefinition()
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($this->className, $this->methodName);
        $returnTypeClassName = $definition->getName();
        $code = $this->config['responses_code'] ?? 200;
        $schema = $this->getSchema($returnTypeClassName);
        if ($this->version === SwaggerJson::SWAGGER_VERSION3) {
            $schema = ['content' => ['application/json' => $schema]];
        }
        $resp[$code] = $schema;
        $resp[$code]['description'] = 'OK';
        return $resp;
    }

    public function generate(): array
    {
        $resp = $this->getMethodDefinition();
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
                $schema['schema']['items'] = new stdClass();
            }
            if ($type == 'object') {
                $schema['schema']['items'] = new stdClass();
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
                if (!empty($apiResponse->type)) {
                    $schema['schema']['type'] = $apiResponse->type;
                    $schema['schema']['items']['$ref'] = $this->getSchema($apiResponse->className)['schema']['$ref'];
                } else {
                    $schema = $this->getSchema($apiResponse->className);
                }
                if ($this->version === SwaggerJson::SWAGGER_VERSION3) {
                    $schema = ['content' => ['application/json' => $schema]];
                }
                $resp[$apiResponse->code] = $schema;
            }
            $resp[$apiResponse->code]['description'] = $apiResponse->description;
        }
        return $resp;
    }
}
