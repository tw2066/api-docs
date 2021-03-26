<?php

namespace App\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Response;

class Contact extends Response
{
    /**
     * @ApiModelProperty(value="名称")
     */
    public string $name;

    /**
     * @ApiModelProperty(value="年龄")
     */
    public int $age;

    /**
     * 需要绝对路径
     * @ApiModelProperty(value="地址")
     * @var \App\DTO\Address[]
     */
    public array $addressArr;

}