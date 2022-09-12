<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractMultipleAnnotation
{
    public function __construct(public ?string $code = null, public string $description = '', public ?string $className = null, public ?string $type = null)
    {
    }
}
