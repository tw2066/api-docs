<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiFormData extends BaseParam
{
    protected $in = 'formData';
}
