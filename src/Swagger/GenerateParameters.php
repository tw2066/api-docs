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
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\PropertyManager;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerInterface;
use Throwable;
use function Hyperf\Support\make;

class GenerateParameters
{
    /**
     * @param ApiHeader[] $apiHeaderArr
     * @param ApiFormData[] $apiFormDataArr
     */
    public function __construct(
        private string $controller,
        private string $action,
        private array $apiHeaderArr,
        private array $apiFormDataArr,
        private ContainerInterface $container,
        private MethodDefinitionCollectorInterface $methodDefinitionCollector,
        private SwaggerComponents $swaggerComponents,
        private SwaggerCommon $common,
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

            if ($this->container->has($parameterClassName)) {
                $methodParameter = MethodParametersManager::getMethodParameter($this->controller, $this->action, $paramName);
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
        //Form表单
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
            $parameter = new OA\Parameter();
            $fieldName = $reflectionProperty->getName();
            $schema = new OA\Schema();
            $parameter->name = $fieldName;
            $parameter->in = $in;
            try {
                $schema->default = $reflectionProperty->getValue(make($parameterClassName));
            } catch (Throwable) {
            }
            $phpType = $this->common->getTypeName($reflectionProperty);
            $propertyManager = PropertyManager::getProperty($parameterClassName, $reflectionProperty->name);
            $enum = $propertyManager?->enum;
            if ($enum) {
                $phpType = $enum->backedType;
            }
            $schema->type = $this->common->getSwaggerType($phpType);

            $apiModelProperty = ApiAnnotation::getProperty($parameterClassName, $fieldName, ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();

            if ($apiModelProperty->hidden  || $propertyManager?->alias) {
                continue;
            }
            if (! $reflectionProperty->isPublic() && ! $rc->hasMethod(\Hyperf\Support\setter($fieldName))) {
                continue;
            }

            $requiredAnnotation = ApiAnnotation::getProperty($parameterClassName, $fieldName, Required::class);
            /** @var In $inFirstAnnotation */
            $inAnnotation = ApiAnnotation::getProperty($parameterClassName, $fieldName, In::class);
            if (! empty($inAnnotation)) {
                $inFirstAnnotation = $inAnnotation->toAnnotations()[0];
                $schema->enum = $inFirstAnnotation->getValue();
            }
            if (! empty($enum)) {
                $schema->enum = $enum->valueList;
            }
            if ($apiModelProperty->required !== null) {
                $parameter->required = $apiModelProperty->required;
            }
            if ($requiredAnnotation !== null) {
                $parameter->required = true;
            }
            $parameter->description = $apiModelProperty->value ?? '';
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    protected function getContent(string $className, string $mediaTypeStr = 'application/json'): array
    {
        $arr = [];
        $mediaType = new OA\MediaType();
        $mediaType->mediaType = $mediaTypeStr;
        $mediaType->schema = $this->getJsonContent($className);
        $arr[] = $mediaType;
        return $arr;
    }

    protected function getJsonContent(string $className): OA\JsonContent
    {
        $jsonContent = new OA\JsonContent();
        $this->swaggerComponents->generateSchemas($className);
        $jsonContent->ref = $this->common->getComponentsName($className);
        return $jsonContent;
    }

    /**
     * @param BaseParam[] $baseParam
     */
    private function getParameterArrByBaseParam(array $baseParam): array
    {
        $parameters = [];
        foreach ($baseParam ?? [] as $param) {
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
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    /**
     * @param BaseParam[] $baseParam
     */
    private function getPropertiesByBaseParam(array $baseParam): array
    {
        $propertyArr = [];
        $requiredArr = [];

        foreach ($baseParam ?? [] as $param) {
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
