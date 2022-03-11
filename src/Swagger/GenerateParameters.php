<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\BaseParam;
use Hyperf\ApiDocs\Collect\ParameterInfo;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\Property;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

class GenerateParameters
{
    use SwaggerCommon;

    public const CONTENT_TYPE_JSON = 'CONTENT_TYPE_JSON';

    public const CONTENT_TYPE_FORM = 'CONTENT_TYPE_FORM';

    public mixed $config;

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    private string $route;

    private string $method;

    private string $controller;

    private string $action;

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
    }

    public function generate(): array
    {
        $consumeType = null;
        $parameters = $this->generateParam($this->apiHeaderArr);

        if (! empty($this->apiFormDataArr)) {
            $parameters = Arr::merge($parameters, $this->generateParam($this->apiFormDataArr));
            $consumeType = self::CONTENT_TYPE_FORM;
        }
        $definitions = $this->methodDefinitionCollector->getParameters($this->controller, $this->action);
        foreach ($definitions as $definition) {
            // query path
            $parameterClassName = $definition->getName();
            $paramName = $definition->getMeta('name');
            $simpleSwaggerType = $this->getSimpleType2SwaggerType($parameterClassName);
            if ($simpleSwaggerType !== null) {
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
                if ($methodParameter == null) {
                    continue;
                }
                if ($methodParameter->isRequestBody()) {
                    $parameterInfo = new ParameterInfo();
                    $parameterInfo->name = $this->getSimpleClassName($parameterClassName);
                    $parameterInfo->in = 'body';
                    $parameterInfo->required = true;
                    $parameterInfo->description = '';

                    $parameterProperty = new Property();
                    $parameterProperty->isSimpleType = false;
                    $parameterProperty->className = $parameterClassName;
                    $parameterInfo->property = $parameterProperty;
                    $parameters[] = $parameterInfo;

                    $consumeType = self::CONTENT_TYPE_JSON;
                }
                if ($methodParameter->isRequestQuery()) {
                    $propertyArr = $this->getParameterClassProperty($parameterClassName, 'query');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($methodParameter->isRequestHeader()) {
                    $propertyArr = $this->getParameterClassProperty($parameterClassName, 'header');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($methodParameter->isRequestFormData()) {
                    $propertyArr = $this->getParameterClassProperty($parameterClassName, 'formData');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                    $consumeType = self::CONTENT_TYPE_FORM;
                }
            }
        }
        return [array_values($parameters), $consumeType];
    }

    /**
     * @param BaseParam[] $paramArr
     * @return ParameterInfo[]
     */
    private function generateParam(array $paramArr): array
    {
        $parameters = [];
        foreach ($paramArr as $param) {
            if ($param->hidden) {
                continue;
            }
            $parameterInfo = new ParameterInfo();
            $parameterInfo->in = $param->getIn();
            $parameterInfo->name = $param->name;
            $parameterInfo->type = $param->type;
            $param->default !== null && $parameterInfo->default = $param->default;
            $param->required !== null && $parameterInfo->required = $param->required;
            $param->description !== null && $parameterInfo->description = $param->description;
            $parameters[] = $parameterInfo;
        }
        return $parameters;
    }
}
