<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class InterfaceSchema
{
    public ?SchemaPaymentInterface $payment = null;

    /** @var SchemaPaymentInterface[] */
    public array $payments = [];

    public array $untyped = [];

    /** @var SchemaPaymentInterface[][] */
    public array $paymentMatrix = [];

    /** @var int[][] */
    public array $intMatrix = [];
}
