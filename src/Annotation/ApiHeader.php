<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class ApiHeader extends BaseParam
{
    public $in = 'header';
}
