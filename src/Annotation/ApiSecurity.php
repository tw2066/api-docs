<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiSecurity extends AbstractMultipleAnnotation
{
    public array $filter = [];

    /**
     * @param string[] $filter
     */
    public function __construct(
        public ?string $name = null,
        public array $value = [],
        array|string $filter = '',
    ) {
        if ($filter) {
            $this->filter = is_string($filter) ? [$filter] : $filter;
        }
    }
}
