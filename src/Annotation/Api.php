<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class Api extends AbstractAnnotation
{
    /**
     * @var array
     */
    public $tags;

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var int
     */
    public $position = 0;

    public function __construct(mixed $tags = null, string $description = '', int $position = 0)
    {
        $this->tags = $tags;
        $this->description = $description;
        $this->position = $position;
    }
}
