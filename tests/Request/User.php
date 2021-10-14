<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class User
{
    #[ApiModelProperty('名称')]
    public string $name;

    #[ApiModelProperty('年龄')]
    public int $age;
}
