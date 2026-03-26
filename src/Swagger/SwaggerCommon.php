<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\DTO\DtoCommon;
use Hyperf\DTO\Type\PhpType;
use OpenApi\Generator;
use ReflectionProperty;
use Throwable;

class SwaggerCommon extends DtoCommon
{
    protected array $classNameCache = [];

    protected array $simpleClassNameCache = [];

    public function getComponentsName(string $className): string
    {
        return '#/components/schemas/' . $this->getSimpleClassName($className);
    }

    public function simpleClassNameClear(): void
    {
        $this->classNameCache = [];
        $this->simpleClassNameCache = [];
    }

    /**
     * 获取简单php类名.
     */
    public function getSimpleClassName(?string $className): string
    {
        if (empty($className)) {
            $className = 'Null';
        }
        $className = ltrim($className, '\\');
        if (isset($this->classNameCache[$className])) {
            return $this->classNameCache[$className];
        }
        $pos = strrpos($className, '\\');
        $simpleClassName = $className;
        if ($pos !== false) {
            $simpleClassName = substr($className, $pos + 1);
        }

        $simpleClassName = $this->getSimpleClassNameNum(ucfirst($simpleClassName));
        $this->classNameCache[$className] = $simpleClassName;
        return $simpleClassName;
    }

    /**
     * 获取swagger类型.
     */
    public function getSwaggerType(mixed $phpType): string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float', 'number' => 'number',
            'array' => 'array',
            'object' => 'object',
            'string' => 'string',
            default => 'null',
        };
    }

    /**
     * 通过PHP类型 获取SwaggerType类型.
     */
    public function getSimpleType2SwaggerType(?string $phpType): ?string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float' => 'number',
            'string', 'mixed' => 'string',
            default => null,
        };
    }

    public function getPhpType(mixed $type): string
    {
        if (is_string($type) && $this->isSimpleType($type)) {
            return $type;
        }
        if ($type instanceof PhpType) {
            return $type->getValue();
        }

        if (is_object($type) && $type::class !== 'stdClass') {
            return '\\' . $type::class;
        }
        if (is_string($type) && class_exists($type)) {
            return '\\' . trim($type, '\\');
        }
        return 'mixed';
    }

    public function getPropertyDefaultValue(string $className, ReflectionProperty $reflectionProperty): mixed
    {
        $default = Generator::UNDEFINED;
        try {
            $obj = \Hyperf\Support\make($className);
            if ($reflectionProperty->isInitialized($obj)) {
                $default = $reflectionProperty->getValue($obj);
            }
        } catch (Throwable) {
            $fieldName = $reflectionProperty->getName();
            $classVars = get_class_vars($className);
            if (isset($classVars[$fieldName])) {
                $default = $classVars[$fieldName];
            }
        }
        return $default;
    }

    private function getSimpleClassNameNum(string $className, int $num = 0): string
    {
        $simpleClassName = $className . ($num > 0 ? '_' . $num : '');
        if (isset($this->simpleClassNameCache[$simpleClassName])) {
            return $this->getSimpleClassNameNum($className, $num + 1);
        }
        $this->simpleClassNameCache[$simpleClassName] = $num;
        return $simpleClassName;
    }
}
