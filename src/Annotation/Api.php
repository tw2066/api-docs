<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\ApiDocs\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\Utils\Str;

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
     * @var string
     */
    public $description = '';

    public function __construct($value = null)
    {
        if (isset($value['tags']) && is_string($value['tags'])){
            $value['tags'] = explode(',', str_replace(' ', '', $value['tags']));
        }
        parent::__construct($value);
    }
}
