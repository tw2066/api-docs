<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\BaseParam;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Contracts\RequestBody;
use Hyperf\DTO\Contracts\RequestFormData;
use Hyperf\DTO\Contracts\RequestQuery;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

class MakeParameters
{
    public $config;

    /**
     * @var MethodDefinitionCollectorInterface|mixed
     */
    private $methodDefinitionCollector;

    private ContainerInterface $container;

    private string $route;

    private string $method;

    private string $controller;

    private string $action;

    private Common $common;

    /**
     * @var \Hyperf\ApiDocs\Annotation\ApiHeader[]
     */
    private array $apiHeaderArr;

    /**
     * @var \Hyperf\ApiDocs\Annotation\ApiFormData[]
     */
    private array $apiFormDataArr;

    public function __construct(
        string $route,
        string $method,
        string $controller,
        string $action,
        array $apiHeaderArr,
        array $apiFormDataArr
    )
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->route = $route;
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
        $this->apiHeaderArr = $apiHeaderArr;
        $this->apiFormDataArr = $apiFormDataArr;
        $this->common = new Common();
    }

    public function make()
    {
        $consumes = null;
        $parameters = $this->makeParam($this->apiHeaderArr);
        if (!empty($this->apiFormDataArr)) {
            $parameters = Arr::merge($parameters, $this->makeParam($this->apiFormDataArr));
            $consumes = 'application/x-www-form-urlencoded';
        }
        $definitions = $this->methodDefinitionCollector->getParameters($this->controller, $this->action);
        foreach ($definitions as $k => $definition) {
            //query path
            $parameterClassName = $definition->getName();
            $simpleSwaggerType = $this->common->simpleType2SwaggerType($parameterClassName);
            if ($simpleSwaggerType !== null) {
                $property = [];
                $property['in'] = 'path';
                $property['name'] = $definition->getMeta('name');
                $property['required'] = true;
                $property['type'] = $simpleSwaggerType;
                $parameters[] = $property;
                continue;
            }

            if ($this->container->has($parameterClassName)) {
                $obj = $this->container->get($parameterClassName);
                if ($obj instanceof RequestBody) {
                    $this->common->class2schema($parameterClassName);
                    $property = [];
                    $property['in'] = 'body';
                    $property['name'] = $this->common->getSimpleClassName($parameterClassName);
                    $property['description'] = '';
                    $property['required'] = true;
                    $property['schema']['$ref'] = $this->common->getDefinitions($parameterClassName);
                    $parameters[] = $property;
                    $consumes = 'application/json';
                }
                if ($obj instanceof RequestQuery) {
                    $propertyArr = $this->common->makePropertyByClass($parameterClassName, 'query');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($obj instanceof RequestFormData) {
                    $propertyArr = $this->common->makePropertyByClass($parameterClassName, 'formData');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                    $consumes = 'application/x-www-form-urlencoded';
                }
            }
        }
        if ($consumes != null) {
            SwaggerJson::$swagger['paths'][$this->route][$this->method]['consumes'] = [$consumes];
        }
        return array_values($parameters);
    }

    private function makeParam($paramArr)
    {
        $parameters = [];
        /** @var BaseParam $param */
        foreach ($paramArr as $param) {
            if ($param->hidden) {
                continue;
            }
            $property = [];
            $property['in'] = $param->in;
            $property['name'] = $param->name;
            $property['type'] = $param->type;
            $param->example !== null && $property['example'] = $param->example;
            $param->default !== null && $property['default'] = $param->default;
            $param->required !== null && $property['required'] = $param->required;
            $param->description !== null && $property['description'] = $param->description;
            $parameters[] = $property;
        }
        return $parameters;
    }
}
