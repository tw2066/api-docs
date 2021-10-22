<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiOperation extends AbstractAnnotation
{
    public string $summary = '';

    public string $description = '';

    public bool $hidden = false;

    public function __construct(string $summary = '', string $description = '', bool $hidden = false)
    {
        $this->summary = $summary;
        $this->description = $description;
        $this->hidden = $hidden;
    }
}
