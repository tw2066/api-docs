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
namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use HyperfExample\ApiDocs\DTO\Address;

class Contact
{
    #[ApiModelProperty('名称')]
    public string $name;

    #[ApiModelProperty('年龄')]
    public ?int $age = null;

    /**
     * 需要绝对路径.
     * @var Address[]
     */
    #[ApiModelProperty('地址')]
    public array $addressArr;
}
