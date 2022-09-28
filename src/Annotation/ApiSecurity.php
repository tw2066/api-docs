<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiSecurity extends AbstractMultipleAnnotation
{
    public array $filter = [];

    public function __construct(
        public string $name = '',
        public array $value = [],
    ) {
    }
}
