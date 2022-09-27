<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractMultipleAnnotation
{
    /**
     * @param string|null $code
     * @param string $description
     * class类或简单类型
     * @param string|null $type
     * @param bool $isArray
     */
    public function __construct(
        public ?string $code = null,
        public string  $description = '',
        public ?string $type = null,
        public bool  $isArray = false
    )
    {
    }
}
