<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiVariable extends AbstractAnnotation
{
    public function __construct(public string $value = '')
    {
    }
}
