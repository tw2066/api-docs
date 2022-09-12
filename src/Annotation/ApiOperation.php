<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiOperation extends AbstractAnnotation
{
    public function __construct(public string $summary = '', public string $description = '', public bool $hidden = false, public ?bool $isSecurity = null)
    {
    }
}
