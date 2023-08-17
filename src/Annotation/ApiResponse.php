<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;
use Hyperf\DTO\Type\PhpType;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractMultipleAnnotation
{
    public null|string|object|array $returnType;

    public function __construct(
        null|string|object|array $returnType = null,
        public string|int|null $response = '200',
        public string $description = 'success',
    ) {
        $this->setReturnType($returnType);
    }

    protected function setReturnType($returnType): void
    {
        if ($returnType instanceof PhpType) {
            $this->returnType = $returnType->value;
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
                $this->returnType = [$returnType[0]->value];
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
