<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use HyperfExample\ApiDocs\Enum\StatusEnum;

#[ApiModel('查询参数')]
class DemoQuery
{
    #[ApiModelProperty('这是一个别名属性')]
    #[JSONField('alias_name')]
    #[Required]
    public string $aliasName = '';

    #[ApiModelProperty('类型')]
    #[In(['a', 'b'])]
    public string $type = '';

    #[ApiModelProperty('状态')]
    public StatusEnum $status = StatusEnum::SUCCESS;
}
