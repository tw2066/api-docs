<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS)]
class ApiModel extends AbstractAnnotation
{
    public function __construct(public string $value = Generator::UNDEFINED)
    {
    }
}
