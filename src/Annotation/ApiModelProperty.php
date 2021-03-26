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

    /**
     * @var string|null
     */
    public  $example = null;

    /**
     * @var bool
     */
    public $hidden = false;

    /**
     * @var bool
     */
    public $required = null;
}
