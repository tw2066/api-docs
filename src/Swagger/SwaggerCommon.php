<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Collect\ParameterInfo;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\ApiAnnotation;
use ReflectionProperty;
use Throwable;

trait SwaggerCommon
{
    /**
     * 获取并收集Definition.
     */
    public function getDefinitionName(string $className): string
    {
        return '#/definitions/' . $this->getSimpleClassName($className);
    }

    /**
     * 获取简单类名.
     */
    public function getSimpleClassName(string $className): string
    {
        return SwaggerJson::getSimpleClassName($className);
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
            $parameterInfo->type = $this->getType2SwaggerType($phpType);
            if (! in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
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
            if (! empty($inAnnotation)) {
                $parameterInfo->enum = $inAnnotation->getValue();
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

    /**
     * 获取SwaggerType类型.
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
