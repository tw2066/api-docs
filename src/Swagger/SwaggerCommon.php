<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Collect\ParameterInfo;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Scan\PropertyManager;
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
     * 获取简单php类名
     * @param string $className
     * @return string
     */
    public function getSimpleClassName(string $className): string
    {
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


    public function getParameterClassProperty(string $parameterClassName, string $in): array
    {

        $parameters = [];
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $parameterInfo = new ParameterInfo();
            $parameterInfo->name = $reflectionProperty->getName();
            $parameterInfo->in = $in;
            try {
                $parameterInfo->default = $reflectionProperty->getValue(make($parameterClassName));
            } catch (Throwable) {
            }
            $phpType = $this->getTypeName($reflectionProperty);
            $enum = PropertyManager::getProperty($phpType,$reflectionProperty->name)?->enum;
            if($enum){
                $phpType = $enum->backedType;
            }

            $parameterInfo->type = $this->common->getSwaggerType($phpType);
            if (! in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
                continue;
            }

            $apiModelProperty = ApiAnnotation::getProperty($parameterClassName, $reflectionProperty->getName(), ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            if ($apiModelProperty->hidden) {
                continue;
            }
            $requiredAnnotation = ApiAnnotation::getProperty($parameterClassName, $reflectionProperty->getName(), Required::class);
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::getProperty($parameterClassName, $reflectionProperty->getName(), In::class);
            if (! empty($inAnnotation)) {
                $parameterInfo->enum = $inAnnotation->getValue();
            }
            if (! empty($enum)) {
                $parameterInfo->enum = $enum->valueList;
            }
            if ($apiModelProperty->required !== null) {
                $parameterInfo->required = $apiModelProperty->required;
            }
            if ($requiredAnnotation !== null) {
                $parameterInfo->required = true;
            }
            $parameterInfo->description = $apiModelProperty->value ?? '';
            $parameters[] = $parameterInfo;
        }
        return $parameters;
    }

    /**
     * 获取PHP类型
     * @param ReflectionProperty $rp
     * @return string
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
     * @return string
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
     * @param string|null $phpType
     * @return string|null
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
     * @return bool
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
