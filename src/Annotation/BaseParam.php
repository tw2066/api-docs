<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractMultipleAnnotation;

abstract class BaseParam extends AbstractMultipleAnnotation
{
    protected string $in;

    public function __construct(
        public string $name = '',
        public ?bool $required = null,
        public string $type = 'string',
        public $default = null,
        public ?string $description = null,
        public bool $hidden = false
    )
    {
    }

    public function getIn(): string
    {
        return $this->in;
    }
}
