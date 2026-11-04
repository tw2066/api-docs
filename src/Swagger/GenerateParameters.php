<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use FastRoute\RouteParser\Std;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\BaseParam;
use Hyperf\Collection\Arr;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\Property;
use Hyperf\DTO\Scan\PropertyManager;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerInterface;

class GenerateParameters
{
    /**
     * @param ApiHeader[] $apiHeaderArr
     * @param ApiFormData[] $apiFormDataArr
     */
    public function __construct(
        protected string $controller,
        protected string $action,
        protected array $apiHeaderArr,
        protected array $apiFormDataArr,
        protected string $route,
        protected ContainerInterface $container,
        protected MethodDefinitionCollectorInterface $methodDefinitionCollector,
        protected SwaggerComponents $swaggerComponents,
        protected SwaggerCommon $common,
        protected PropertyManager $propertyManager,
        protected MethodParametersManager $methodParametersManager
    ) {}

    public function generate(): array
    {
        $result = [
            'requestBody' => [],
        ];
        // FormData类名
        $requestFormDataclass = '';
        $parameterArr = $this->getParameterArrByBaseParam($this->apiHeaderArr);
        $definitions = $this->methodDefinitionCollector->getParameters($this->controller, $this->action);
        foreach ($definitions as $definition) {
            // query path
            $parameterClassName = $definition->getName();
            $paramName = $definition->getMeta('name');
            // 判断是否为简单类型
            $simpleSwaggerType = $this->common->getSimpleType2SwaggerType($parameterClassName);
            if ($simpleSwaggerType !== null) {
                if (! $this->isPathParam($paramName)) {
                    continue;
                }
                $parameter = new OA\Parameter();
                $parameter->required = true;
                $parameter->name = $paramName;
                $parameter->in = 'path';
                $schema = new OA\Schema();
                $schema->type = $simpleSwaggerType;
                $parameter->schema = $schema;
                $parameterArr[] = $parameter;
                continue;
            }

            $methodParameter = $this->methodParametersManager->getMethodParameter($this->controller, $this->action, $paramName);
            if ($parameterClassName === 'array' && $methodParameter?->isRequestBody()) {
                $requestBody = new OA\RequestBody();
                $requestBody->required = true;
                $property = $this->methodParametersManager->getProperty($this->controller, $this->action, $paramName);
                $requestBody->content = $this->getContent('array', property: $property);
                $result['requestBody'] = $requestBody;
            }

            $resolvedParameterClass = $this->common->resolveDtoClass($parameterClassName) ?? $parameterClassName;
            if ($this->container->has($resolvedParameterClass)
                || class_exists($resolvedParameterClass)
                || interface_exists($resolvedParameterClass)
            ) {
                if ($methodParameter == null) {
                    continue;
                }
                if ($methodParameter->isRequestBody()) {
                    $requestBody = new OA\RequestBody();
                    $requestBody->required = true;
                    $requestBody->content = $this->getContent($resolvedParameterClass);
                    $result['requestBody'] = $requestBody;
                }
                if ($methodParameter->isRequestQuery()) {
                    $parameterArr = array_merge($parameterArr, $this->getParameterArrByClass($resolvedParameterClass, 'query'));
                }
                if ($methodParameter->isRequestHeader()) {
                    $parameterArr = array_merge($parameterArr, $this->getParameterArrByClass($resolvedParameterClass, 'header'));
                }
                if ($methodParameter->isRequestFormData()) {
                    $requestFormDataclass = $resolvedParameterClass;
                }
            }
        }
        // Form表单
        if (! empty($requestFormDataclass) || ! empty($this->apiFormDataArr)) {
            $requestBody = new OA\RequestBody();
            $requestBody->required = true;
            // $requestBody->description = '';
            $mediaType = new OA\MediaType();
            $mediaType->mediaType = 'multipart/form-data';
            // $parameterClassName
            $mediaType->schema = $this->generateFormDataSchemas($requestFormDataclass, $this->apiFormDataArr);
            $mediaType->schema->type = 'object';
            $requestBody->content = [];
            $requestBody->content[$mediaType->mediaType] = $mediaType;
            $result['requestBody'] = $requestBody;
        }

        $result['parameter'] = $parameterArr;
        return $result;
    }

    public function generateFormDataSchemas($className, $apiFormDataArr): OA\Schema
    {
        $schema = new OA\Schema();
        $data = $this->swaggerComponents->getProperties($className);
        $annotationData = $this->getPropertiesByBaseParam($apiFormDataArr);
        $schema->properties = Arr::merge($data['propertyArr'], $annotationData['propertyArr']);
        $schema->required = Arr::merge($data['requiredArr'], $annotationData['requiredArr']);
        return $schema;
    }

    public function getParameterArrByClass(string $parameterClassName, string $in): array
    {
        $parameters = [];
        $this->common->syncDtoMapper();
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            if ($reflectionProperty->isStatic()) {
                continue;
            }
            $apiModelProperty = ApiAnnotation::getProperty($parameterClassName, $fieldName, ApiModelProperty::class) ?: new ApiModelProperty();
            if ($apiModelProperty->hidden) {
                continue;
            }
            $requiredAnnotation = ApiAnnotation::getProperty($parameterClassName, $fieldName, Required::class)?->toAnnotations()[0];
            $isRequired = $apiModelProperty->required || $requiredAnnotation !== null;
            [, $accessor, $accessorType] = $this->common->inspectDtoProperty($rc, $fieldName);
            if ($accessor === null && ! $isRequired) {
                continue;
            }

            $propertyManager = $this->propertyManager->getProperty($parameterClassName, $fieldName);
            $schema = $apiModelProperty->phpType
                ? $this->swaggerComponents->createSchema(type: $apiModelProperty->phpType, namespace: $rc->getNamespaceName())
                : $this->swaggerComponents->createSchema($propertyManager, $accessorType, $rc->getNamespaceName());
            if ($propertyManager?->phpSimpleType !== 'array') {
                $schema->default = $this->common->getPropertyDefaultValue($parameterClassName, $reflectionProperty);
            }
            if (empty($apiModelProperty->phpType)) {
                $inAnnotation = ApiAnnotation::getProperty($parameterClassName, $fieldName, In::class)?->toAnnotations()[0];
                if ($inAnnotation !== null) {
                    $schema->enum = $inAnnotation->getValue();
                }
            }

            $parameter = new OA\Parameter();
            $parameter->name = $propertyManager?->alias ?? $fieldName;
            $parameter->in = $in;
            if ($isRequired) {
                $parameter->required = true;
            }
            $parameter->schema = $schema;
            $parameter->description = $apiModelProperty->value ?? '';
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    protected function getContent(string $className, string $mediaTypeStr = 'application/json', ?Property $property = null): array
    {
        $arr = [];
        $mediaType = new OA\MediaType();
        $mediaType->mediaType = $mediaTypeStr;
        $mediaType->schema = $this->getJsonContent($className, $property);
        $arr[] = $mediaType;
        return $arr;
    }

    protected function getJsonContent(string $className, ?Property $property = null): Schema
    {
        return $this->swaggerComponents->createSchema($property, $className);
    }

    /**
     * @param BaseParam[] $baseParam
     */
    protected function getParameterArrByBaseParam(array $baseParam): array
    {
        $parameters = [];
        foreach ($baseParam as $param) {
            if ($param->hidden) {
                continue;
            }
            $parameter = new OA\Parameter();
            $schema = new OA\Schema();
            $parameter->name = $param->name;
            $parameter->in = $param->getIn();
            $schema->default = $param->default;
            $type = $this->common->getSwaggerType($param->type);
            if ($type !== null) {
                $schema->type = $type;
            }
            // 描述
            $parameter->description = $param->description;
            if ($param->required !== null) {
                $parameter->required = $param->required;
            }
            $schema->default = $param->default;
            $schema->format = $param->format;
            $parameter->schema = $schema;
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    /**
     * @param BaseParam[] $baseParam
     */
    protected function getPropertiesByBaseParam(array $baseParam): array
    {
        $propertyArr = [];
        $requiredArr = [];

        foreach ($baseParam as $param) {
            if ($param->hidden) {
                continue;
            }
            // 属性
            $property = new OA\Property();
            // 字段名称
            $fieldName = $param->name;
            $property->property = $fieldName;
            // 描述
            $property->description = $param->description;
            $param->required && $requiredArr[] = $fieldName;
            $property->default = $param->default;
            $type = $this->common->getSwaggerType($param->type);
            if ($type !== null) {
                $property->type = $type;
            }
            $property->format = $param->format;
            $propertyArr[] = $property;
        }
        return ['propertyArr' => $propertyArr, 'requiredArr' => $requiredArr];
    }

    /**
     * 判断参数是否为路由路径占位符（复用 FastRoute 解析器，与框架路由匹配规则保持一致）.
     */
    protected function isPathParam(string $paramName): bool
    {
        $routeDataList = (new Std())->parse($this->route);
        foreach ($routeDataList as $routeData) {
            foreach ($routeData as $segment) {
                if (is_array($segment) && $segment[0] === $paramName) {
                    return true;
                }
            }
        }
        return false;
    }
}
