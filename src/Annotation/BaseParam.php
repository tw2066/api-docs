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

    /**
     * @var string
     */
    protected $in;

    /**
     * BaseParam constructor.
     * @param bool $required
     * @param null $default
     * @param null $example
     */
    public function __construct(string $name = '', bool $required = null, string $type = 'string', $default = null, $example = null, string $description = null, bool $hidden = false)
    {
        $this->name = $name;
        $this->required = $required;
        $this->type = $type;
        $this->default = $default;
        $this->example = $example;
        $this->description = $description;
        $this->hidden = $hidden;
    }

    public function getIn(): string
    {
        return $this->in;
    }
}
