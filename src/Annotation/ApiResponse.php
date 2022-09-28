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
        public string|int|null $response = null,
        public string $description = '',
        public ?string $type = null,
        public bool $isArray = false,
    ) {
    }
}
