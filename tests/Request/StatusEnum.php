<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

enum StatusEnum: string implements StatusInterface
{
    case Active = 'active';
    case Disabled = 'disabled';
}
