<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayType extends AbstractAnnotation
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
