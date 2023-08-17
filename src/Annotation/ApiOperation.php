<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiOperation extends AbstractAnnotation
{
    public function __construct(
        public string $summary = '',
        public string $description = Generator::UNDEFINED,
        public bool $hidden = false,
        public bool $security = true,
        public bool $deprecated = false
    ) {
    }
}
