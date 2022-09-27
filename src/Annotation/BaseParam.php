<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractMultipleAnnotation;
use OpenApi\Generator;

abstract class BaseParam extends AbstractMultipleAnnotation
{
    protected string $in;

    public function __construct(
        public string $name,
        public ?bool $required = null,
        public string $type = 'string',
        public $default = Generator::UNDEFINED,
        public string $description = Generator::UNDEFINED,
        public $format = Generator::UNDEFINED,
        public bool $hidden = false
    )
    {
    }

    public function getIn(): string
    {
        return $this->in;
    }
}
