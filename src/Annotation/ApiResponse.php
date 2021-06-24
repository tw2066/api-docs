<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $className;

    /**
     * @var string
     */
    public $type;

    /**
     * ApiResponse constructor.
     */
    public function __construct(string $code = null, string $description = null, string $className = null, string $type = null)
    {
        $this->code = $code;
        $this->description = $description;
        $this->className = $className;
        $this->type = $type;
    }
}
