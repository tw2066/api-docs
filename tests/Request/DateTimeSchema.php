<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class DateTimeSchema
{
    public \DateTime $createdAt;

    /** @var \DateTime[] */
    public array $paidAt = [];
}
