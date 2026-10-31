<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\ApiDocs\Annotation\ApiVariable;

class ProxyWithHelper
{
    #[ApiVariable]
    public array $content = [];
}

class ProxyHelper {}
