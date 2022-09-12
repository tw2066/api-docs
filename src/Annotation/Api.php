<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class Api extends AbstractAnnotation
{
    public function __construct(public mixed $tags = null, public string $description = '', public int $position = 0, public bool $hidden = false)
    {
    }
}
