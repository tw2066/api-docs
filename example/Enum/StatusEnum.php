<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\Enum;

enum StatusEnum: string
{
    case SUCCESS = 'success';
    case CLOSED = 'closed';
}
