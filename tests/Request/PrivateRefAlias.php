<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\JSONField;

class PrivateRefAlias
{
    #[JSONField('the_address')]
    private ?Address $address = null;
}
