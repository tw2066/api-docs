<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\JSONField;

class PrivateAlias
{
    #[JSONField('name_arr')]
    private array $nameArr = [];

    private string $internal = 'hidden';
}
