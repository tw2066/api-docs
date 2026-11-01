<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiVariable;

#[ApiModel('分页结果')]
class Page
{
    #[ApiModelProperty('总数')]
    public int $total = 0;

    #[ApiVariable]
    #[ApiModelProperty('列表')]
    public array $content = [];

    public function __construct(array $content = [], int $total = 0)
    {
        $this->content = $content;
        $this->total = $total;
    }
}
