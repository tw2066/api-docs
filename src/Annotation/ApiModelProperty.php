<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiModelProperty extends AbstractAnnotation
{
    public function __construct(
        public ?string $value = null,
        public mixed $example = Generator::UNDEFINED,
        public bool $hidden = false,
        public ?bool $required = null)
    {
    }
}
