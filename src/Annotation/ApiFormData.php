<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ApiFormData extends BaseParam
{
    public $in = 'formData';
}
