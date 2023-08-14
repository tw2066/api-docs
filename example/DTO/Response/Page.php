<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiVariable;

class Page
{
    public int $total;

    #[ApiVariable]
    public array $content;

    public function __construct(array $content, int $total = 0)
    {
        $this->content = $content;
        $this->total = $total;
    }
}
