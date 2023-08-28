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
    protected static array $className = [];

    protected static array $simpleClassName = [];

    public function getComponentsName(string $className): string
    {
        return '#/components/schemas/' . $this->getSimpleClassName($className);
    }

    /**
     * 获取简单php类名.
     */
    public function getSimpleClassName(?string $className): string
    {
        if ($className === null) {
            $className = 'Null';
        }
        $className = ltrim($className, '\\');
        if (isset(self::$className[$className])) {
            return self::$className[$className];
        }
        $pos = strrpos($className, '\\');
        $simpleClassName = $className;
        if ($pos !== false) {
            $simpleClassName = substr($className, $pos + 1);
        }

        $simpleClassName = $this->getSimpleClassNameNum(ucfirst($simpleClassName));
        self::$className[$className] = $simpleClassName;
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

        if (is_object($type) && $type::class != 'stdClass') {
            return '\\' . $type::class;
        }
        if (is_string($type) && class_exists($type)) {
            return '\\' . trim($type, '\\');
        }
        return 'mixed';
    }

    public function getPropertyDefaultValue(string $className, ReflectionProperty $reflectionProperty)
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
            // 别名会获取不到默认值
            if (isset($classVars[$fieldName])) {
                $default = $classVars[$fieldName];
            }
        }
        return $default;
    }

    private function getSimpleClassNameNum(string $className, $num = 0): string
    {
        $simpleClassName = $className . ($num > 0 ? '_' . $num : '');
        if (isset(self::$simpleClassName[$simpleClassName])) {
            return $this->getSimpleClassNameNum($className, $num + 1);
        }
        self::$simpleClassName[$simpleClassName] = $num;
        return $simpleClassName;
    }
}
