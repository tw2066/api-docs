<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\DTO\Scan\Scan;
use Hyperf\DTO\Type\PhpType;

class SwaggerCommon
{
    private static array $className;

    private static array $simpleClassName;

    public function __construct(private Scan $scan)
    {
    }

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

        $className = ucfirst($className);
        $className = '\\' . trim($className, '\\');
        if (isset(self::$className[$className])) {
            return self::$className[$className];
        }
        $simpleClassName = substr($className, strrpos($className, '\\') + 1);
        if (isset(self::$simpleClassName[$simpleClassName])) {
            $simpleClassName .= '-' . (++self::$simpleClassName[$simpleClassName]);
        } else {
            self::$simpleClassName[$simpleClassName] = 0;
        }
        self::$className[$className] = $simpleClassName;
        return $simpleClassName;
    }

    /**
     * 获取PHP类型.
     */
    public function getTypeName(\ReflectionProperty $rprop): string
    {
        return $this->scan->getTypeName($rprop);
    }

    /**
     * 获取swagger类型.
     * @param mixed $phpType
     */
    public function getSwaggerType($phpType): string
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

    /**
     * 判断是否为简单类型.
     */
    public function isSimpleType(mixed $type): bool
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }

    public function getPhpType(mixed $type): string
    {
        if (is_string($type) && $this->isSimpleType($type)) {
            return $type;
        }
        if ($type instanceof PhpType) {
            return $type->value;
        }

        if (is_object($type) && $type::class != 'stdClass') {
            return '\\' . $type::class;
        }
        if (is_string($type) && class_exists($type)) {
            return '\\' . trim($type, '\\');
        }
        return 'mixed';
    }
}
