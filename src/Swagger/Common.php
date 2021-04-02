<?php

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\ApiAnnotation;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Scan\PropertyManager;
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
            } catch (Throwable $exception) {
            }
            $phpType = $this->getTypeName($reflectionProperty);
            $property['type'] = $this->type2SwaggerType($phpType);
            if (!in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
                continue;
            }
            $apiModelProperty = new ApiModelProperty();
            $requiredAnnotation = null;
            $propertyReflectionPropertyArr = ApiAnnotation::propertyMetadata($parameterClassName, $reflectionProperty->getName());
            foreach ($propertyReflectionPropertyArr as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
                if ($propertyReflectionProperty instanceof Required) {
                    $requiredAnnotation = $propertyReflectionProperty;
                }
            }
            if ($apiModelProperty->hidden) {
                continue;
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
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $rc = ReflectionManager::reflectClass($className);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $propertyClass = PropertyManager::getProperty($className,$fieldName);
            $phpType = $propertyClass->type;
            $type = $this->type2SwaggerType($phpType);
            $apiModelProperty = new ApiModelProperty();
            $propertyReflectionPropertyArr = ApiAnnotation::propertyMetadata($className, $fieldName);
            foreach ($propertyReflectionPropertyArr as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
            }
            if ($apiModelProperty->hidden) {
                continue;
            }
            $property = [];
            $property['type'] = $type;
            $property['description'] = $apiModelProperty->value ?? '';
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
            }
            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
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
            if (!$propertyClass->isSimpleType && class_exists($propertyClass->className)) {
                $this->class2schema($propertyClass->className);
                $property['$ref'] = $this->getDefinitions($propertyClass->className);
            }
            $schema['properties'][$fieldName] = $property;
        }
        SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] = $schema;
    }


    public function isSimpleType($type)
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }

}