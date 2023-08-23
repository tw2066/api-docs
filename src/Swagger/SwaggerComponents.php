<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\DtoConfig;
use Hyperf\DTO\Scan\PropertyManager;
use OpenApi\Attributes as OA;

class SwaggerComponents
{
    protected static array $schemas = [];

    public function __construct(
        private SwaggerCommon $common,
    ) {
    }

    public function getSchemas(): array
    {
        return self::$schemas;
    }

    public function setSchemas(array $schemas): void
    {
        self::$schemas = $schemas;
    }

    public function getProperties(string $className): array
    {
        if (empty($className)) {
            return ['propertyArr' => [], 'requiredArr' => []];
        }

        $rc = ReflectionManager::reflectClass($className);
        $propertyArr = [];
        $requiredArr = [];
        $classVars = get_class_vars($className);
        // 循环类中字段
        foreach ($rc->getProperties() as $reflectionProperty) {
            // 属性
            $property = new OA\Property();
            $fieldName = $reflectionProperty->getName();
            $propertyManager = PropertyManager::getProperty($className, $fieldName);

            $apiModelProperty = ApiAnnotation::getProperty($className, $fieldName, ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::getProperty($className, $fieldName, In::class)?->toAnnotations()[0];
            if ($apiModelProperty->hidden || $propertyManager->alias) {
                continue;
            }
            if (! $reflectionProperty->isPublic()
                && ! $rc->hasMethod(\Hyperf\Support\setter($fieldName))
                && ! $rc->hasMethod(DtoConfig::getDtoAliasMethodName($fieldName))
            ) {
                continue;
            }
            // 字段名称
            $property->property = $fieldName;
            // 描述
            $apiModelProperty->value !== null && $property->description = $apiModelProperty->value;
            // required
            /** @var Required $requiredAnnotation */
            $requiredAnnotation = ApiAnnotation::getProperty($className, $fieldName, Required::class)?->toAnnotations()[0];
            if ($apiModelProperty->required || $requiredAnnotation) {
                $requiredArr[] = $fieldName;
            }
            $property->example = $apiModelProperty->example;

            if (array_key_exists($fieldName, $classVars)) {
                $property->default = $classVars[$fieldName];
            }

            // swagger 类型
            $swaggerType = $this->common->getSwaggerType($propertyManager->phpSimpleType);

            // 枚举:in
            if (! empty($inAnnotation)) {
                $property->type = $swaggerType;
                $property->enum = $inAnnotation->getValue();
            }
            // 简单类型
            elseif ($propertyManager->isSimpleType) {
                // 数组
                if ($swaggerType == 'array') {
                    $property->type = 'array';
                    $items = new OA\Items();
                    $items->type = 'null';
                    $property->items = $items;
                } else {
                    // 普通简单类型
                    $property->type = $swaggerType;
                }
            } // 枚举类型
            elseif ($propertyManager->enum) {
                $property->type = $this->common->getSwaggerType($propertyManager->enum->backedType);
                $property->enum = $propertyManager->enum->valueList;
            } // 普通类
            else {
                if ($swaggerType == 'array') {
                    $property->type = 'array';
                    if (! empty($propertyManager->arrClassName)) {
                        $items = new OA\Items();
                        $items->ref = $this->common->getComponentsName($propertyManager->arrClassName);
                        $property->items = $items;
                        $this->generateSchemas($propertyManager->arrClassName);
                    } elseif (! empty($propertyManager->arrSimpleType)) {
                        $items = new OA\Items();
                        $items->type = $this->common->getSwaggerType($propertyManager->arrSimpleType);
                        $property->items = $items;
                    }
                } elseif (! empty($propertyManager->className)) {
                    $property->ref = $this->common->getComponentsName($propertyManager->className);
                    $this->generateSchemas($propertyManager->className);
                } else {
                    throw new ApiDocsException("field:{$className}-{$fieldName} type resolved not found");
                }
            }
            $propertyArr[] = $property;
        }
        return ['propertyArr' => $propertyArr, 'requiredArr' => $requiredArr];
    }

    public function generateSchemas(string $className)
    {
        $simpleClassName = $this->common->getSimpleClassName($className);
        if (isset(static::$schemas[$simpleClassName])) {
            return static::$schemas[$simpleClassName];
        }
        $schema = new OA\Schema();
        $schema->schema = $simpleClassName;

        $data = $this->getProperties($className);
        $schema->properties = $data['propertyArr'];
        /** @var ApiModel $apiModel */
        $apiModel = AnnotationCollector::getClassAnnotation($className, ApiModel::class);
        if ($apiModel) {
            $schema->description = $apiModel->value;
        }
        $data['requiredArr'] && $schema->required = $data['requiredArr'];
        self::$schemas[$simpleClassName] = $schema;
        return self::$schemas[$simpleClassName];
    }
}
