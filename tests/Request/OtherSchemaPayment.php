<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class OtherSchemaPayment implements SchemaPaymentInterface
{
    public string $account = '';
}
