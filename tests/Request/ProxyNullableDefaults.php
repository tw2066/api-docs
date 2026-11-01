<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\ApiDocs\Annotation\ApiVariable;

class ProxyNullableDefaults
{
    #[ApiVariable]
    public mixed $data = null;

    #[ApiVariable]
    public mixed $content = null;

    public function __construct(mixed $data = null, mixed $content = null)
    {
        $this->data = $data;
        $this->content = $content;
    }
}
