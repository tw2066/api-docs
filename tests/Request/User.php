<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\ApiDocs\Request;


use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class User
{
    #[ApiModelProperty('名称')]
    public string $name;

    #[ApiModelProperty('年龄')]
    public int $age;
}
