<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\BaseParam;
use Hyperf\ApiDocs\Collect\MainCollect;
use Hyperf\ApiDocs\Collect\ParameterInfo;
use Hyperf\ApiDocs\Collect\Schema;
use Hyperf\ApiDocs\Collect\SchemaItems;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\Property;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

class GenerateParameters
{
    public mixed $config;

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    private string $route;

    private string $method;

    private string $controller;

    private string $action;

    private SwaggerCommon $common;

    /**
     * @var ApiHeader[]
     */
    private array $apiHeaderArr;

    /**
     * @var ApiFormData[]
     */
    private array $apiFormDataArr;

    public function __construct(
        string $route,
        string $method,
        string $controller,
        string $action,
        array $apiHeaderArr,
        array $apiFormDataArr
    ) {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->route = $route;
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
        $this->apiHeaderArr = $apiHeaderArr;
        $this->apiFormDataArr = $apiFormDataArr;
        $this->common = new SwaggerCommon();
    }

    public function generate(): array
    {
        $consumes = null;
        $parameters = $this->generateParam($this->apiHeaderArr);

        if (! empty($this->apiFormDataArr)) {
            $parameters = Arr::merge($parameters, $this->generateParam($this->apiFormDataArr));
            $consumes = 'application/x-www-form-urlencoded';
        }
        $definitions = $this->methodDefinitionCollector->getParameters($this->controller, $this->action);
        foreach ($definitions as $definition) {
            //query path
            $parameterClassName = $definition->getName();
            $paramName = $definition->getMeta('name');
            $simpleSwaggerType = $this->common->getSimpleType2SwaggerType($parameterClassName);
            if ($simpleSwaggerType !== null) {
//                $property = [];
//                $property['in'] = 'path';
//                $property['name'] = $paramName;
//                $property['required'] = true;
//                $property['type'] = $simpleSwaggerType;
//                $parameters[] = $property;

                $parameterInfo = new ParameterInfo();
                $parameterInfo->name = $paramName;
                $parameterInfo->in = 'path';
                $parameterInfo->required = true;
                $parameterInfo->type = $simpleSwaggerType;
                $parameters[] = $parameterInfo;
                continue;
            }

            if ($this->container->has($parameterClassName)) {
                $methodParameter = MethodParametersManager::getMethodParameter($this->controller, $this->action, $paramName);
                if ($methodParameter->isRequestBody()) {
//                    $this->common->generateClass2schema($parameterClassName);
//                    $property = [];
//                    $property['in'] = 'body';
//                    $property['name'] = $this->common->getSimpleClassName($parameterClassName);
//                    $property['description'] = '';
//                    $property['required'] = true;
//                    $property['schema']['$ref'] = $this->common->getDefinitions($parameterClassName);
//                    $parameters[] = $property;


                    $parameterInfo = new ParameterInfo();
                    $parameterInfo->name = $this->common->getSimpleClassName($parameterClassName);
                    $parameterInfo->in = 'body';
                    $parameterInfo->required = true;
                    $parameterInfo->description = '';

//                    $parameterInfo->isSimpleType = false;
//
//                    $parameterInfo->className = $parameterClassName;

                    $parameterProperty = new Property();
                    $parameterProperty->isSimpleType = false;
                    $parameterProperty->className = $parameterClassName;
//                    dd($parameterProperty);

                    $parameterInfo->property = $parameterProperty;

//                    public bool $isSimpleType;
//
//    public ?string $phpType= null;
//
//    public ?string $className = null;


//                    $schemaItems = new SchemaItems();
//                    $schemaItems->ref = $this->common->getDefinitions($parameterClassName);
//                    $schema = new Schema();
//                    $schema->type = 'object';
//                    $schema->items = $schemaItems;
//                    $parameterInfo->schema = $schema;
//                    MainCollect::setDefinitionClass($parameterClassName);
                    $parameters[] = $parameterInfo;

                    $consumes = 'application/json';
                }
                if ($methodParameter->isRequestQuery()) {
                    $propertyArr = $this->common->getParameterClassProperty($parameterClassName, 'query');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($methodParameter->isRequestFormData()) {
                    $propertyArr = $this->common->getParameterClassProperty($parameterClassName, 'formData');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                    $consumes = 'application/x-www-form-urlencoded';
                }
            }
        }
        return [array_values($parameters),$consumes ? [$consumes] : []];
    }


    public function generate2(): array
    {
        $consumes = null;
        $parameters = $this->generateParam($this->apiHeaderArr);
        if (! empty($this->apiFormDataArr)) {
            $parameters = Arr::merge($parameters, $this->generateParam($this->apiFormDataArr));
            $consumes = 'application/x-www-form-urlencoded';
        }
        $definitions = $this->methodDefinitionCollector->getParameters($this->controller, $this->action);
        foreach ($definitions as $definition) {
            //query path
            $parameterClassName = $definition->getName();
            $paramName = $definition->getMeta('name');
            $simpleSwaggerType = $this->common->getSimpleType2SwaggerType($parameterClassName);
            if ($simpleSwaggerType !== null) {
                $property = [];
                $property['in'] = 'path';
                $property['name'] = $paramName;
                $property['required'] = true;
                $property['type'] = $simpleSwaggerType;
                $parameters[] = $property;
                continue;
            }

            if ($this->container->has($parameterClassName)) {
                $methodParameter = MethodParametersManager::getMethodParameter($this->controller, $this->action, $paramName);
                if ($methodParameter->isRequestBody()) {
                    $this->common->generateClass2schema($parameterClassName);
                    $property = [];
                    $property['in'] = 'body';
                    $property['name'] = $this->common->getSimpleClassName($parameterClassName);
                    $property['description'] = '';
                    $property['required'] = true;
                    $property['schema']['$ref'] = $this->common->getDefinitions($parameterClassName);
                    $parameters[] = $property;
                    $consumes = 'application/json';
                }
                if ($methodParameter->isRequestQuery()) {
                    $propertyArr = $this->common->getParameterClassProperty($parameterClassName, 'query');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($methodParameter->isRequestFormData()) {
                    $propertyArr = $this->common->getParameterClassProperty($parameterClassName, 'formData');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                    $consumes = 'application/x-www-form-urlencoded';
                }
            }
        }
        return [array_values($parameters),[$consumes]];
    }

    private function generateParam($paramArr): array
    {
        $parameters = [];
        /** @var BaseParam $param */
        foreach ($paramArr as $param) {
            if ($param->hidden) {
                continue;
            }
//            $property = [];
//            $property['in'] = $param->getIn();
//            $property['name'] = $param->name;
//            $property['type'] = $param->type;
//            $param->example !== null && $property['example'] = $param->example;
//            $param->default !== null && $property['default'] = $param->default;
//            $param->required !== null && $property['required'] = $param->required;
//            $param->description !== null && $property['description'] = $param->description;
//            $parameters[] = $property;

            $parameterInfo = new ParameterInfo();
            $parameterInfo->in = $param->getIn();
            $parameterInfo->name = $param->name;
            $parameterInfo->type = $param->type;
            $param->example !== null && $parameterInfo->example = $param->example;
            $param->default !== null && $parameterInfo->default = $param->default;
            $param->required !== null && $parameterInfo->required = $param->required;
            $param->description !== null && $parameterInfo->description = $param->description;

            $parameters[] = $parameterInfo;
        }
        return $parameters;
    }
}
