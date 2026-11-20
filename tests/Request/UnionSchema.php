<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class UnionSchema
{
    public OtherSchemaPayment|SchemaPayment $value;
}
