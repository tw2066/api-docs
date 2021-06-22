<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

abstract class BaseParam extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $name = '';

    /**
     * @var bool
     */
    public $required;

    /**
     * @var string
     */
    public $type = 'string';

    public $default;

    public $example;

    /**
     * @var string
     */
    public $description;

    /**
     * @var bool
     */
    public $hidden = false;
}
