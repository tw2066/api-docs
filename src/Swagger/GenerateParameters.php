<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

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
use Hyperf\DTO\DtoConfig;
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\Property;
use Hyperf\DTO\Scan\PropertyManager;
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
        protected ContainerInterface $container,
        protected MethodDefinitionCollectorInterface $methodDefinitionCollector,
        protected SwaggerComponents $swaggerComponents,
        protected SwaggerCommon $common,
        protected PropertyManager $propertyManager,
        protected MethodParametersManager $methodParametersManager
    ) {
    }

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
                $requestBody->content = $this->getContent($property->arrClassName ?? '', property: $property);
                $result['requestBody'] = $requestBody;
            }

            if ($this->container->has($parameterClassName)) {
                if ($methodParameter == null) {
                    continue;
                }
                if ($methodParameter->isRequestBody()) {
                    $requestBody = new OA\RequestBody();
                    $requestBody->required = true;
                    // $requestBody->description = '';
                    $requestBody->content = $this->getContent($parameterClassName);
                    $result['requestBody'] = $requestBody;
                }
                if ($methodParameter->isRequestQuery()) {
                    $parameterArr = array_merge($parameterArr, $this->getParameterArrByClass($parameterClassName, 'query'));
                }
                if ($methodParameter->isRequestHeader()) {
                    $parameterArr = array_merge($parameterArr, $this->getParameterArrByClass($parameterClassName, 'header'));
                }
                if ($methodParameter->isRequestFormData()) {
                    $requestFormDataclass = $parameterClassName;
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
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $propertyManager = $this->propertyManager->getProperty($parameterClassName, $reflectionProperty->name);
            $parameter = new OA\Parameter();
            $fieldName = $reflectionProperty->getName();
            $schema = new OA\Schema();
            $parameter->name = $propertyManager?->alias ?? $fieldName;
            $parameter->in = $in;
            $schema->default = $this->common->getPropertyDefaultValue($parameterClassName, $reflectionProperty);

            $apiModelProperty = ApiAnnotation::getProperty($parameterClassName, $fieldName, ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            if ($apiModelProperty->hidden) {
                continue;
            }
            if (! $reflectionProperty->isPublic()
                && ! $rc->hasMethod(\Hyperf\Support\setter($fieldName))
                && ! $rc->hasMethod(DtoConfig::getDtoAliasMethodName($fieldName))
            ) {
                continue;
            }
            // 存在自定义简单类型
            if ($apiModelProperty->phpType) {
                $phpType = $apiModelProperty->phpType;
            } else {
                $phpType = $this->common->getTypeName($reflectionProperty);
                $enum = $propertyManager?->enum;
                if ($enum) {
                    $phpType = $enum->backedType;
                }
                /** @var In $inAnnotation */
                $inAnnotation = ApiAnnotation::getProperty($parameterClassName, $fieldName, In::class)?->toAnnotations()[0];
                if (! empty($inAnnotation)) {
                    $schema->enum = $inAnnotation->getValue();
                }
                if (! empty($enum)) {
                    $schema->enum = $enum->valueList;
                }
            }

            $schema->type = $this->common->getSwaggerType($phpType);

            /** @var Required $requiredAnnotation */
            $requiredAnnotation = ApiAnnotation::getProperty($parameterClassName, $fieldName, Required::class)?->toAnnotations()[0];
            if ($apiModelProperty->required || $requiredAnnotation) {
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

    protected function getJsonContent(string $className, ?Property $property = null): OA\JsonContent
    {
        $jsonContent = new OA\JsonContent();
        $this->swaggerComponents->generateSchemas($className);
        if ($property?->phpSimpleType == 'array') {
            $jsonContent->type = 'array';
            $items = new OA\Items();
            if ($property->arrClassName) {
                $items->ref = $this->common->getComponentsName($property->arrClassName);
            } else {
                $items->type = $this->common->getSwaggerType($property->arrSimpleType);
            }
            $jsonContent->items = $items;
        } else {
            $jsonContent->ref = $this->common->getComponentsName($className);
        }
        return $jsonContent;
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
            $schema->type = $this->common->getSwaggerType($param->type);
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
            $property->type = $this->common->getSwaggerType($param->type);
            $property->format = $param->format;
            $propertyArr[] = $property;
        }
        return ['propertyArr' => $propertyArr, 'requiredArr' => $requiredArr];
    }
}
