<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Scan\Property;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

class SwaggerComponents
{
    protected array $schemas = [];

    public function __construct(
        protected SwaggerCommon $common,
        protected PropertyManager $propertyManager,
        protected ?GenerateProxyClass $generateProxyClass,
    ) {}

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function setSchemas(array $schemas): void
    {
        $this->schemas = $schemas;
    }

    public function getProperties(string $className): array
    {
        if (empty($className)) {
            return ['propertyArr' => [], 'requiredArr' => []];
        }

        $this->common->syncDtoMapper();
        $rc = ReflectionManager::reflectClass($className);
        $propertyArr = [];
        $requiredArr = [];
        foreach ($rc->getProperties() as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            if ($reflectionProperty->isStatic()) {
                continue;
            }
            $propertyManager = $this->propertyManager->getProperty($className, $fieldName);
            if ($propertyManager === null) {
                // 属性未被 DTO 扫描器登记（如代理类），按反射类型兜底。
                $propertyManager = $this->buildPropertyFromReflection($reflectionProperty);
            }

            $sourceClassName = $this->generateProxyClass?->getSourceClassname($className) ?? $className;
            $apiModelProperty = ApiAnnotation::getProperty($sourceClassName, $fieldName, ApiModelProperty::class) ?: new ApiModelProperty();
            if ($apiModelProperty->hidden) {
                continue;
            }
            $requiredAnnotation = ApiAnnotation::getProperty($sourceClassName, $fieldName, Required::class)?->toAnnotations()[0];
            $isRequired = $apiModelProperty->required || $requiredAnnotation !== null;
            [, $accessor] = $this->common->inspectDtoProperty($rc, $fieldName);
            $isWritable = $accessor !== null;
            if (! $reflectionProperty->isPublic()
                && ! $isWritable
                && $propertyManager->alias === null
                && ! $isRequired
            ) {
                continue;
            }

            $property = new OA\Property();
            $property->property = $propertyManager->alias ?? $fieldName;
            $apiModelProperty->value !== null && $property->description = $apiModelProperty->value;
            if ($isRequired) {
                // 验证发生在 DTO 映射前，不可写属性仍可能是请求必填字段。
                $requiredArr[] = $property->property;
            }
            $property->example = $apiModelProperty->example;
            // 数组默认值会被 Knife4j 生成请求示例时序列化成字符串（如 "[]"），且属性默认值并非服务端默认值，数组不写入 default。
            if ($propertyManager->phpSimpleType !== 'array') {
                $property->default = $this->common->getPropertyDefaultValue($className, $reflectionProperty);
            }
            $schema = $apiModelProperty->phpType
                ? $this->createSchema(type: $apiModelProperty->phpType, namespace: $rc->getNamespaceName())
                : $this->createSchema($propertyManager);
            $inAnnotation = ApiAnnotation::getProperty($sourceClassName, $fieldName, In::class)?->toAnnotations()[0];
            if ($inAnnotation !== null && empty($apiModelProperty->phpType)) {
                $schema->enum = $inAnnotation->getValue();
            }
            $this->copySchemaType($schema, $property);
            if (! $isWritable && ! $isRequired) {
                $this->markReadOnly($property);
            }
            $propertyArr[] = $property;
        }
        return ['propertyArr' => $propertyArr, 'requiredArr' => $requiredArr];
    }

    public function createSchema(?Property $property = null, ?string $type = null, string $namespace = ''): Schema
    {
        $property ??= $this->propertyManager->createPropertyFromType($type, $namespace);
        $schema = new OA\Schema();
        $this->applySchemaType($schema, $property);
        return $schema;
    }

    public function generateSchemas(string $className)
    {
        $simpleClassName = $this->common->getSimpleClassName($className);
        if (isset($this->schemas[$simpleClassName])) {
            return $this->schemas[$simpleClassName];
        }
        $schema = new OA\Schema();
        $schema->schema = $simpleClassName;
        // 先登记再解析属性，防止循环引用类（A ↔ B）导致无限递归。
        $this->schemas[$simpleClassName] = $schema;

        $data = $this->getProperties($className);
        $schema->properties = $data['propertyArr'];
        /** @var ApiModel $apiModel */
        $apiModel = AnnotationCollector::getClassAnnotation($className, ApiModel::class);
        if ($apiModel) {
            $schema->description = $apiModel->value;
        }
        $data['requiredArr'] && $schema->required = $data['requiredArr'];
        return $this->schemas[$simpleClassName];
    }

    protected function applySchemaType(Schema $schema, Property $property): void
    {
        if ($property->enum !== null) {
            $this->applyEnum($schema, $property->enum);
            return;
        }
        if ($property->phpSimpleType === 'array') {
            $this->applyArrayType($schema, $property);
            return;
        }
        if ($property->className !== null) {
            $this->applyClassType($schema, $property->className);
            return;
        }
        if ($property->interfaceName !== null) {
            $this->applyClassType($schema, $property->interfaceName);
            return;
        }

        $type = $this->common->getSwaggerType($property->phpSimpleType);
        if ($type !== null) {
            $schema->type = $type;
        }
    }

    protected function applyArrayType(Schema $schema, Property $property): void
    {
        $depth = max(1, $property->arrayDepth);
        $current = $schema;
        for ($level = 0; $level < $depth; ++$level) {
            $current->type = 'array';
            $current->items = new OA\Items();
            $current = $current->items;
        }

        if ($property->arrClassName !== null) {
            $this->applyClassType($current, $property->arrClassName);
            return;
        }
        if ($property->arrInterfaceName !== null) {
            $this->applyClassType($current, $property->arrInterfaceName);
            return;
        }
        $type = $this->common->getSwaggerType($property->arrSimpleType);
        if ($type !== null) {
            $current->type = $type;
            if ($type === 'array') {
                $current->items = new OA\Items();
            }
        }
    }

    protected function applyClassType(Schema $schema, string $className): void
    {
        $mappedClass = $this->common->resolveDtoClass($className);
        if ($mappedClass === null) {
            // 接口或动态 classMap 无法在启动期确定具体结构。
            $schema->type = 'object';
            return;
        }
        if (enum_exists($mappedClass)) {
            $this->applyEnumClass($schema, $mappedClass);
            return;
        }

        $schema->ref = $this->common->getComponentsName($mappedClass);
        $this->generateSchemas($mappedClass);
    }

    protected function applyEnumClass(Schema $schema, string $enumClass): void
    {
        $propertyEnum = (new PropertyEnum())->get($enumClass);
        if ($propertyEnum !== null) {
            $this->applyEnum($schema, $propertyEnum);
            return;
        }

        $schema->type = 'string';
        $schema->enum = array_map(static fn ($case) => $case->name, $enumClass::cases());
    }

    protected function applyEnum(Schema $schema, PropertyEnum $propertyEnum): void
    {
        $schema->type = $this->common->getSwaggerType($propertyEnum->backedType);
        $schema->enum = $propertyEnum->valueList;
    }

    protected function copySchemaType(Schema $source, Schema $target): void
    {
        foreach (['type', 'ref', 'items', 'enum'] as $field) {
            if ($source->{$field} !== Generator::UNDEFINED) {
                $target->{$field} = $source->{$field};
            }
        }
    }

    protected function markReadOnly(OA\Property $property): void
    {
        $property->readOnly = true;
        if ($property->ref === Generator::UNDEFINED) {
            return;
        }

        // swagger-php 序列化 $ref 时会丢弃同级字段，使用 allOf 保留 readOnly。
        $ref = new OA\Schema();
        $ref->ref = $property->ref;
        $property->ref = Generator::UNDEFINED;
        $property->allOf = [$ref];
    }

    protected function buildPropertyFromReflection(\ReflectionProperty $reflectionProperty): Property
    {
        return $this->propertyManager->createPropertyFromType(
            $this->common->getTypeName($reflectionProperty),
            $reflectionProperty->getDeclaringClass()->getNamespaceName(),
        );
    }
}
