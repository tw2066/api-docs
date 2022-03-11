<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Header;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;

class DemoToken
{
    #[ApiModelProperty(value: '名称', required: true)]
    public string $name;

    #[Between(2, 10)]
    public string $token;

    #[ApiModelProperty(value: '名称', required: true)]
    public int $age;
}
