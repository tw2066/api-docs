<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiModelProperty extends AbstractAnnotation
{
    public string $value = '';

    public mixed $column;

    public mixed $example;

    public bool $hidden = false;

    public ?bool $required;

    public function __construct(string $value = '', $column = true, $example = null, bool $hidden = false, bool $required = null)
    {
        $this->value = $value;
        $this->example = $example;
        $this->hidden = $hidden;
        $this->required = $required;
        $this->column = $column;
    }
}
