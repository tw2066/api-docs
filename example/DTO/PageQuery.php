<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;

#[ApiModel('分页查询参数')]
class PageQuery
{
    #[ApiModelProperty('页数')]
    public ?int $page = null;

    #[ApiModelProperty('每页数量')]
    public ?int $size = null;
}
