<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;

#[ApiModel('表单提交数据')]
class DemoFormData
{
    #[ApiModelProperty(value: '名称', required: true)]
    public string $name = '';

    #[ApiModelProperty(value: '数量', example: 2)]
    #[Between(min: 2, max: 10)]
    public int $num = 2;

    #[ApiModelProperty(value: '年龄', required: true)]
    public int $age = 0;
}
