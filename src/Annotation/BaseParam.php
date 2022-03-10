<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

abstract class BaseParam extends AbstractMultipleAnnotation
{
    public string $name = '';

    public ?bool $required;

    public string $type = 'string';

    public mixed $default;

    public ?string $description;

    public bool $hidden = false;

    protected string $in;

    public function __construct(string $name = '', bool $required = null, string $type = 'string', $default = null, string $description = null, bool $hidden = false)
    {
        $this->name = $name;
        $this->required = $required;
        $this->type = $type;
        $this->default = $default;
        $this->description = $description;
        $this->hidden = $hidden;
    }

    public function getIn(): string
    {
        return $this->in;
    }
}
