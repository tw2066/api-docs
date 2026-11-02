<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class CardPayment extends AbstractPayment
{
    public string $cardNo = '';
}
