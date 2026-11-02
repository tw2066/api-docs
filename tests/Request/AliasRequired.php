<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;

class AliasRequired
{
    #[JSONField('display_name')]
    #[Required]
    public string $displayName = '';
}
