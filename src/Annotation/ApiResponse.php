<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractMultipleAnnotation
{
    /**
     * @param null|int|string $response
     * @param null|string $type class类或简单类型
     */
    public function __construct(
        public null|string|object|array $returnType = null,
        public string|int|null $response = '200',
        public string $description = 'success',
    ) {
    }
}
