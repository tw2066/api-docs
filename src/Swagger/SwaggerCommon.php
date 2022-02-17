<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\Database\Model\Model;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\Utils\ApplicationContext;
use ReflectionProperty;
use stdClass;
use Throwable;

class SwaggerCommon
{
    private int $version;

    public function __construct(int $version)
    {
        $this->version = $version;
    }

    public function getDefinitions(string $className): string
    {
        if ($this->version === SwaggerJson::SWAGGER_VERSION3) {
            return '#/components/schemas/' . $this->getSimpleClassName($className);
        } else {
            return '#/definitions/' . $this->getSimpleClassName($className);
        }
    }

    public function pushDefinitions(string $className, array $schema): void
    {
        if ($this->version === SwaggerJson::SWAGGER_VERSION3) {
            SwaggerJson::$swagger['components']['schemas'][$this->getSimpleClassName($className)] = $schema;
        } else {
            SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] = $schema;
        }
    }

    protected function getDefinition(string $className): array
    {
        if ($this->version === SwaggerJson::SWAGGER_VERSION3) {
            return SwaggerJson::$swagger['components']['schemas'][$this->getSimpleClassName($className)] ?? [];
        } else {
            return SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] ?? [];
        }
    }

    public function getSimpleClassName(string $className): string
    {
        return SwaggerJson::getSimpleClassName($className);
    }

    public function getParameterClassProperty(string $parameterClassName, string $in): array
    {
        $parameters = [];
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) ?? [] as $reflectionProperty) {
            $property = [];
            $property['in'] = $in;
            $property['name'] = $reflectionProperty->getName();
            try {
                $property['default'] = $reflectionProperty->getValue(make($parameterClassName));
            } catch (Throwable) {
            }
            $phpType = $this->getTypeName($reflectionProperty);
            $property['type'] = $this->getType2SwaggerType($phpType);
            if (!in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
                continue;
            }

            $apiModelProperty = ApiAnnotation::getProperty($parameterClassName, $reflectionProperty->getName(), ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            $requiredAnnotation = ApiAnnotation::getProperty($parameterClassName, $reflectionProperty->getName(), Required::class);
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::getProperty($parameterClassName, $reflectionProperty->getName(), In::class);
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

    public function getTypeName(ReflectionProperty $rp): string
    {
        try {
            $type = $rp->getType()->getName();
        } catch (Throwable) {
            $type = 'string';
        }
        return $type;
    }

    public function getType2SwaggerType($phpType): string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float' => 'number',
            'array' => 'array',
            'object' => 'object',
            default => 'string',
        };
    }

    public function getSimpleType2SwaggerType(string $phpType): ?string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float' => 'number',
            'string', 'mixed' => 'string',
            default => null,
        };
    }

    public function generateClass2schema(string $className): void
    {
        if (!ApplicationContext::getContainer()->has($className)) {
            $this->generateEmptySchema($className);
            return;
        }
        $obj = ApplicationContext::getContainer()->get($className);
        if ($obj instanceof Model) {
            //$this->getModelSchema($obj);
            $this->generateEmptySchema($className);
            return;
        }

        $schema = [
            'type'       => 'object',
            'properties' => [],
        ];
        $rc = ReflectionManager::reflectClass($className);
        foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $propertyClass = PropertyManager::getProperty($className, $fieldName);
            $phpType = $propertyClass->type;
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
                        $property['items']['type'] = $this->getType2SwaggerType($propertyClass->className);
                    } else {
                        $this->generateClass2schema($propertyClass->className);
                        $property['items']['$ref'] = $this->getDefinitions($propertyClass->className);
                    }
                }
            }
            if ($type == 'object') {
                $property['items'] = (object)[];
            }
            if (!$propertyClass->isSimpleType && $phpType != 'array' && class_exists($propertyClass->className)) {
                $this->generateClass2schema($propertyClass->className);
                if (!empty($property['description'])) {
                    $definition = $this->getDefinition($propertyClass->className);
                    $definition['description'] = $property['description'];
                    $this->pushDefinitions($propertyClass->className, $definition);
                }
                $property = ['$ref' => $this->getDefinitions($propertyClass->className)];
            }
            $schema['properties'][$fieldName] = $property;
        }
        if (empty($schema['properties'])) {
            $schema['properties'] = new stdClass();
        }
        $this->pushDefinitions($className, $schema);
    }

    public function isSimpleType($type): bool
    {
        return $type == 'string' || $type == 'boolean' || $type == 'bool' || $type == 'integer' || $type == 'int' || $type == 'double' || $type == 'float' || $type == 'array' || $type == 'object';
    }

    protected function generateEmptySchema(string $className)
    {
        $this->pushDefinitions($className, [
            'type'       => 'object',
            'properties' => new stdClass(),
        ]);
    }

    protected function getModelSchema(object $model)
    {
        //$reflect = new ReflectionObject($model);
        //$docComment = $reflect->getDocComment();
    }
}
