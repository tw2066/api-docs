<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiModelProperty extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $value = '';

    public $example;

    /**
     * @var bool
     */
    public $hidden = false;

    /**
     * @var bool
     */
    public $required;

    /**
     * ApiModelProperty constructor.
     * @param string $value
     * @param null $example
     * @param bool $hidden
     * @param bool $required
     */
    public function __construct(string $value = '', $example = null, bool $hidden = false, bool $required = null)
    {
        $this->value = $value;
        $this->example = $example;
        $this->hidden = $hidden;
        $this->required = $required;
    }
}
