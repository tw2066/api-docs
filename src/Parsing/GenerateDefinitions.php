<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\Database\Model\Model;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Scan\Property;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;

class GenerateDefinitions
{
    use SwaggerCommon;

    protected static array $definitions;

    public function __construct()
    {
        static::$definitions = [];
    }

    public function getDefinitions(): array
    {
        $definitions = static::$definitions;
        static::$definitions = [];
        return $definitions;
    }

    public function getItems(Property $property): array
    {
        $items = [];
        $swaggerType = $this->getType2SwaggerType($property->phpType);
        $className = $property->className;
        //简单类型
        if ($property->isSimpleType) {
            $items['type'] = $swaggerType;
            if ($swaggerType == 'array') {
                $items['items']['type'] = 'string';
            }
            return $items;
        }

        if ($swaggerType == 'array') {
            $items['type'] = 'array';
            if (! empty($className)) {
                if (class_exists($className)) {
                    $items['items']['$ref'] = $this->getDefinitionName($className);
                    $this->generateClass2Definition($className);
                } else {
                    $items['items']['type'] = $this->getType2SwaggerType($className);
                }
            }
        } elseif (! empty($className) && class_exists($className)) {
            $items['$ref'] = $this->getDefinitionName($className);
            $this->generateClass2Definition($className);
        }
        return $items;
    }

    public function generateClass2Definition(string $className): void
    {
        //generateDefinitions
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $simpleClassName = $this->getSimpleClassName($className);
        if (! ApplicationContext::getContainer()->has($className)) {
            static::$definitions[$simpleClassName] = $schema;
            return;
        }
        $obj = ApplicationContext::getContainer()->get($className);
        if ($obj instanceof Model) {
            static::$definitions[$simpleClassName] = $schema;
            return;
        }
        $rc = ReflectionManager::reflectClass($className);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $propertyClass = PropertyManager::getProperty($className, $fieldName);
            $phpType = $propertyClass->phpType;
            $type = $this->getType2SwaggerType($phpType);
            $apiModelProperty = ApiAnnotation::getProperty($className, $fieldName, ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::getProperty($className, $reflectionProperty->getName(), In::class);

            if ($apiModelProperty->hidden) {
                continue;
            }
            $property = [];
            $property['type'] = $type;
            if (! empty($inAnnotation)) {
                $property['enum'] = $inAnnotation->getValue();
            }
            $property['description'] = $apiModelProperty->value ?? '';
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
            }
            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
            }
            if ($reflectionProperty->isPublic() && $reflectionProperty->isInitialized($obj)) {
                $property['default'] = $reflectionProperty->getValue($obj);
            }
            $items = $this->getItems($propertyClass);
            $property = Arr::merge($property, $items);
            $schema['properties'][$fieldName] = $property;
        }
        static::$definitions[$simpleClassName] = $schema;
    }
}
