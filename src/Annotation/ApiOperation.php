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

    public function __construct($summary = '', $description = '')
    {
        $this->summary = $summary;
        $this->description = $description;
    }
}
