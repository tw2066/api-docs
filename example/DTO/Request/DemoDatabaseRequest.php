<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Exists;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Unique;

#[ApiModel('数据库验证请求')]
class DemoDatabaseRequest
{
    #[ApiModelProperty('这是一个Exists验证')]
    #[Required]
    #[Exists(table: 'sale', column: 'id')]
    public int $id = 1;

    #[ApiModelProperty('这是一个Unique验证')]
    #[Required]
    #[Unique(table: 'sale', column: 'product', ignoreIdKey: 'id')]
    public float $uniqueProduct = 1.3;
}
