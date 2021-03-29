<?php

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\ApiAnnotation;
use Hyperf\Di\ReflectionManager;
use ReflectionProperty;
use JsonMapper;
use Throwable;

class Common extends JsonMapper
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
            $propertyReflectionPropertyArr = ApiAnnotation::propertyMetadata($parameterClassName, $reflectionProperty->getName());
            foreach ($propertyReflectionPropertyArr as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
            }
            if ($apiModelProperty->hidden) {
                continue;
            }
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
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
        $strNs = $rc->getNamespaceName();
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $type = $this->getTypeName($reflectionProperty);
            $fieldName = $reflectionProperty->getName();
            $type = $this->type2SwaggerType($type);
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

            if ($type == 'array') {
                $docblock = $reflectionProperty->getDocComment();
                $annotations = static::parseAnnotations($docblock);
                if (empty($annotations)) {
                    $property['items']['$ref'] = '#/definitions/ModelArray';
                } else {
                    //support "@var type description"
                    list($type) = explode(' ', $annotations['var'][0]);
                    $type = $this->getFullNamespace($type, $strNs);
                    if ($this->isArrayOfType($type)) {
                        $subtype = substr($type, 0, -2);
                        if ($this->isSimpleType($subtype)) {
                            $property['items']['type'] = $this->type2SwaggerType($subtype);
                        } else {
                            $this->class2schema($subtype);
                            $property['items']['$ref'] = $this->getDefinitions($subtype);
                        }
                    }
                }
            }
            if ($type == 'object') {
                $property['items']['$ref'] = '#/definitions/ModelObject';
            }

            if (!$this->isSimpleType($type) && class_exists($type)) {
                $this->class2schema($type);
                $property['$ref'] = $this->getDefinitions($type);
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