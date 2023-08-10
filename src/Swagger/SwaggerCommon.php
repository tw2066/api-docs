<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use ReflectionProperty;
use Throwable;

class SwaggerCommon
{
    private static array $className;

    private static array $simpleClassName;

    public function getComponentsName(string $className): string
    {
        return '#/components/schemas/' . $this->getSimpleClassName($className);
    }

    /**
     * 获取简单php类名.
     */
    public function getSimpleClassName(?string $className): string
    {
        if($className === null){
            $className = 'Null';
        }

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
    public function getTypeName(ReflectionProperty $rp): string
    {
        try {
            $type = $rp->getType()->getName();
        } catch (Throwable) {
            $type = 'string';
        }
        return $type;
    }

    /**
     * 获取swagger类型.
     * @param $phpType
     */
    public function getSwaggerType($phpType): string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float','number' => 'number',
            'array' => 'array',
            'object' => 'object',
            default => 'string',
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
     * @param $type
     */
    public function isSimpleType($type): bool
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }
}
