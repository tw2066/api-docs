<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Header;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;

#[ApiModel('认证请求头')]
class DemoToken
{
    #[ApiModelProperty(value: '名称', required: true)]
    public string $name = '';

    #[ApiModelProperty(value: '令牌', example: 'abcdef')]
    #[Between(min: 2, max: 10)]
    public string $token = '';

    #[ApiModelProperty(value: '年龄', required: true)]
    public int $age = 0;
}
