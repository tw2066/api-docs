<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
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
    public $required = null;

    /**
     * @var string
     */
    public $type = 'string';

    public $default = null;

    public $example = null;

    public $description = null;

    /**
     * @var bool
     */
    public $hidden = false;

}
