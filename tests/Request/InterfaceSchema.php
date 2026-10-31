<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

interface SchemaPaymentInterface {}

class InterfaceSchema
{
    public ?SchemaPaymentInterface $payment = null;

    /** @var SchemaPaymentInterface[] */
    public array $payments = [];

    public array $untyped = [];
}

class SchemaPayment implements SchemaPaymentInterface
{
    public string $provider = '';
}
