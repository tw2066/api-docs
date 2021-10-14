<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiHeader extends BaseParam
{
    protected string $in = 'header';
}
