<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\ApiAnnotation;
use Hyperf\Database\Model\Model;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\Utils\ApplicationContext;
use ReflectionProperty;
use Throwable;

class Common
{
    public function getDefinitions($className)
    {
        return '#/definitions/' . $this->getSimpleClassName($className);
    }

    public function getSimpleClassName($className)
    {
        return SwaggerJson::getSimpleClassName($className);
    }

    public function makePropertyByClass(string $parameterClassName, string $in)
    {
        $parameters = [];
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $property = [];
            $property['in'] = $in;
            $property['name'] = $reflectionProperty->getName();
            try {
                $property['default'] = $reflectionProperty->getValue(make($parameterClassName));
            } catch (Throwable) {
            }
            $phpType = $this->getTypeName($reflectionProperty);
            $property['type'] = $this->type2SwaggerType($phpType);
            if (!in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
                continue;
            }

            $apiModelProperty = ApiAnnotation::property($parameterClassName, $reflectionProperty->getName(), ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            $requiredAnnotation = ApiAnnotation::property($parameterClassName, $reflectionProperty->getName(), Required::class);
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::property($parameterClassName, $reflectionProperty->getName(), In::class);
            if ($apiModelProperty->hidden) {
                continue;
            }
            if (!empty($inAnnotation)) {
                $property['enum'] = $inAnnotation->getValue();
            }
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
            }
            if ($requiredAnnotation !== null) {
                $property['required'] = true;
            }
            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
            }
            $property['description'] = $apiModelProperty->value ?? '';
            $parameters[] = $property;
        }
        return $parameters;
    }

    public function getTypeName(ReflectionProperty $rp)
    {
        try {
            $type = $rp->getType()->getName();
        } catch (Throwable $throwable) {
            $type = 'string';
        }
        return $type;
    }

    public function type2SwaggerType($phpType)
    {
        switch ($phpType) {
            case 'int':
            case 'integer':
                $type = 'integer';
                break;
            case 'boolean':
            case 'bool':
                $type = 'boolean';
                break;
            case 'double':
            case 'float':
                $type = 'number';
                break;
            case 'array':
                $type = 'array';
                break;
            case 'object':
                $type = 'object';
                break;
            default:
                $type = 'string';
        }
        return $type;
    }

    public function simpleType2SwaggerType($phpType)
    {
        $type = null;
        switch ($phpType) {
            case 'int':
            case 'integer':
                $type = 'integer';
                break;
            case 'boolean':
            case 'bool':
                $type = 'boolean';
                break;
            case 'double':
            case 'float':
                $type = 'number';
                break;
            case 'string':
            case 'mixed':
                $type = 'string';
        }
        return $type;
    }

    public function class2schema($className)
    {
        if (!ApplicationContext::getContainer()->has($className)) {
            return $this->emptySchema($className);
        }
        $obj = ApplicationContext::getContainer()->get($className);
        if ($obj instanceof Model) {
            //$this->makeModelSchema($obj);
            return $this->emptySchema($className);
        }

        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $rc = ReflectionManager::reflectClass($className);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $propertyClass = PropertyManager::getProperty($className, $fieldName);
            $phpType = $propertyClass->type;
            $type = $this->type2SwaggerType($phpType);
            $apiModelProperty = ApiAnnotation::property($className, $fieldName, ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::property($className, $reflectionProperty->getName(), In::class);

            if ($apiModelProperty->hidden) {
                continue;
            }
            $property = [];
            $property['type'] = $type;
            if (!empty($inAnnotation)) {
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
            if ($phpType == 'array') {
                if ($propertyClass->className == null) {
                    $property['items'] = (object)[];
                } else {
                    if ($propertyClass->isSimpleType) {
                        $property['items']['type'] = $this->type2SwaggerType($propertyClass->className);
                    } else {
                        $this->class2schema($propertyClass->className);
                        $property['items']['$ref'] = $this->getDefinitions($propertyClass->className);
                    }
                }
            }
            if ($type == 'object') {
                $property['items'] = (object)[];
            }
            if (!$propertyClass->isSimpleType && $phpType != 'array' && class_exists($propertyClass->className)) {
                $this->class2schema($propertyClass->className);
                $property['$ref'] = $this->getDefinitions($propertyClass->className);
            }
            $schema['properties'][$fieldName] = $property;
        }
        SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] = $schema;
    }

    public function isSimpleType($type): bool
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }

    protected function emptySchema($className)
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] = $schema;
    }

    protected function makeModelSchema(object $model)
    {
        //$reflect = new ReflectionObject($model);
        //$docComment = $reflect->getDocComment();
    }
}
