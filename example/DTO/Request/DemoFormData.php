<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;

class DemoFormData
{
    #[ApiModelProperty(value: '名称', required: true)]
    private string $name;

    #[Between(2, 10)]
    private int $num;

    #[ApiModelProperty(value: '名称', required: true)]
    private int $age;
}
