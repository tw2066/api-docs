<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;

#[ApiModel('只读响应')]
class ReadonlyResponse
{
    #[ApiModelProperty('记录 ID')]
    public readonly string $id;

    #[ApiModelProperty('创建时间')]
    public readonly string $createdAt;

    public function __construct(string $id = '', string $createdAt = '')
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
    }
}
