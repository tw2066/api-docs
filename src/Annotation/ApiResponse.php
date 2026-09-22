<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;
use Hyperf\DTO\Type\PhpType;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractMultipleAnnotation
{
    public null|array|object|string $returnType = null;

    /**
     * @param null|array|object|string $returnType 返回类型: 类名/简单类型字符串、实例(可变类型场景), 或以上种类的单元素数组(表示数组)
     * @param null|int|string $response HTTP状态码
     * @param string $description 响应描述
     * @param array<string, mixed> $types 可变属性类型映射: #[ApiVariable]属性名 => 类名字符串/PhpType/实例/单元素数组(表示数组), 无需实例化即可生成代理类; 仅当 returnType 为单个类名时生效
     */
    public function __construct(
        null|array|object|string $returnType = null,
        public null|int|string $response = '200',
        public string $description = 'success',
        public array $types = [],
    ) {
        $this->setReturnType($returnType);
        $this->setTypes($types);
    }

    protected function setTypes(array $types): void
    {
        foreach ($types as $property => $type) {
            if (! is_string($property)) {
                throw new ApiDocsException('ApiResponse: types key must be a property name string');
            }
            $value = is_array($type) ? ($type[0] ?? null) : $type;
            if ($value instanceof PhpType || is_object($value)) {
                continue;
            }
            if (is_string($value) && ($this->isSimpleType($value) || class_exists($value))) {
                continue;
            }
            throw new ApiDocsException('ApiResponse: Unsupported types value for property ' . $property);
        }
    }

    protected function isSimpleType(string $type): bool
    {
        return in_array($type, ['int', 'integer', 'string', 'float', 'double', 'bool', 'boolean', 'array', 'object', 'mixed'], true);
    }

    protected function setReturnType($returnType): void
    {
        if ($returnType instanceof PhpType) {
            $this->returnType = $returnType->getValue();
            return;
        }
        if (is_object($returnType)) {
            $this->returnType = $returnType;
            return;
        }
        if (is_string($returnType) && class_exists($returnType)) {
            $this->returnType = $returnType;
            return;
        }
        // eg: [class]
        if (is_array($returnType) && count($returnType) > 0) {
            if ($returnType[0] instanceof PhpType) {
                $this->returnType = [$returnType[0]->getValue()];
                return;
            }
            if (is_string($returnType[0]) && class_exists($returnType[0])) {
                $this->returnType = $returnType;
                return;
            }
            if (is_object($returnType[0])) {
                $this->returnType = $returnType;
                return;
            }
        }
        // 空数组
        if (is_array($returnType) && count($returnType) == 0) {
            $this->returnType = 'array';
            return;
        }

        if ($returnType === null) {
            $this->returnType = null;
            return;
        }

        throw new ApiDocsException('ApiResponse: Unsupported data type');
    }
}
