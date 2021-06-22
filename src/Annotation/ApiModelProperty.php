<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
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
}
