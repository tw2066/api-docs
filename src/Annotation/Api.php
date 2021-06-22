<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Api extends AbstractAnnotation
{
    /**
     * @var array
     */
    public $tags;

    /**
     * @var int
     */
    public $position = 0;

    /**
     * @var string
     */
    public $description = '';

//    public function __construct($value = null)
//    {
//        if (isset($value['tags']) && is_string($value['tags'])) {
//            $value['tags'] = explode(',', str_replace(' ', '', $value['tags']));
//        }
//        parent::__construct($value);
//    }
}
