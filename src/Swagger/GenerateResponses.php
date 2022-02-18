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
        $resp = [];
        $AnnotationResp = $this->getAnnotationResp();
        if (empty($AnnotationResp[200])) {
            $resp = $this->getMethodDefinition();
        }
        $globalResp = $this->getGlobalResp();
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

    protected function getSchemaForTemplate(array $template, ?string $type, string $ref)
    {
        $schema = [
            'type'       => 'object',
            'properties' => [],
        ];
        foreach ($template as $key => $value) {
            [$filed, $description] = explode('|', $key);
            $schema['properties'][$filed] = [
                'type'        => $this->common->getType2SwaggerType(gettype($value)),
                'description' => $description,
            ];
            if ($value === '{template}') {
                $schema['properties'][$filed] = $type === 'array' ? ['type' => $type, 'items' => $ref ? ['$ref' => $ref] : new stdClass()] : ['$ref' => $ref];
            }
            if (is_array($value)) {
                $item = $this->getSchemaForTemplate($value, $type, $ref);
                $item['description'] = $description;
                $templateClassName = implode('', array_map(function ($connect) {
                    return ucfirst($connect);
                }, [SwaggerJson::getSimpleClassName($this->className), $this->methodName, $filed]));
                $this->common->pushDefinitions($templateClassName, $item);
                $schema['properties'][$filed] = ['$ref' => $this->common->getDefinitions($templateClassName)];
            }
        }
        return $schema;
    }

    private function getAnnotationResp(): array
    {
        $resp = [];
        /** @var ApiResponse $apiResponse */
        foreach ($this->apiResponseArr as $apiResponse) {
            $schema = [];
            if ($apiResponse->className) {
                $scanAnnotation = $this->container->get(ScanAnnotation::class);
                $scanAnnotation->scanClass($apiResponse->className);
                $this->getSchema($apiResponse->className);
                if (!empty($apiResponse->type) && empty($apiResponse->template)) {
                    $schema['schema']['type'] = $apiResponse->type;
                    $schema['schema']['items']['$ref'] = $this->getSchema($apiResponse->className)['schema']['$ref'];
                } else {
                    $schema = $this->getSchema($apiResponse->className);
                }
            }

            if ($apiResponse->template) {
                $template = $this->config['templates'][$apiResponse->template] ?? [];
                if (!empty($template)) {
                    if (empty($schema)) {
                        $schema['schema']['$ref'] = $apiResponse->type !== 'array' ? $this->common->getEmptyDefinition('object') : '';
                    }

                    if (isset($schema['schema']['$ref'])) {
                        $templateSchema = $this->getSchemaForTemplate($template, $apiResponse->type, $schema['schema']['$ref'] ?? '');
                        $templateClassName = implode('', array_map(function ($connect) {
                            return ucfirst($connect);
                        }, [SwaggerJson::getSimpleClassName($this->className), $this->methodName]));
                        $this->common->pushDefinitions($templateClassName, $templateSchema);
                        $schema['schema']['$ref'] = $this->common->getDefinitions($templateClassName);
                    }

                    if (isset($schema['schema']['items']['$ref'])) {
                        $templateSchema = $this->getSchemaForTemplate($template, $apiResponse->type, $schema['schema']['items']['$ref'] ?? '');
                        $templateClassName = implode('', array_map(function ($connect) {
                            return ucfirst($connect);
                        }, [SwaggerJson::getSimpleClassName($this->className), $this->methodName]));
                        $this->common->pushDefinitions($templateClassName, $templateSchema);
                        $schema['schema']['items']['$ref'] = $this->common->getDefinitions($templateClassName);
                    }
                }
            }

            if (!empty($schema)) {
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
