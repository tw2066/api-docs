<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\ApiDocs\Annotation\ApiVariable;

class Page
{
    public int $total = 0;

    #[ApiVariable]
    public array $content;

    public function __construct(array $content = [], int $total = 0)
    {
        $this->content = $content;
        $this->total = $total;
    }
}
