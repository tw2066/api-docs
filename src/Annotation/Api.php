<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class Api extends AbstractAnnotation
{
    public mixed $tags;

    public string $description = '';

    public int $position = 0;

    public bool $hidden = false;

    public function __construct(mixed $tags = null, string $description = '', int $position = 0, bool $hidden = false)
    {
        $this->tags = $tags;
        $this->description = $description;
        $this->position = $position;
        $this->hidden = $hidden;
    }
}
