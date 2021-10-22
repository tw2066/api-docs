<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiFormData extends BaseParam
{
    protected string $in = 'formData';
}
