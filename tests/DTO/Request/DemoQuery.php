<?php

namespace App\DTO\Request;

use Tang\ApiDocs\Annotation\ApiModelProperty;
use Tang\DTO\Contracts\RequestQuery;

class DemoQuery implements RequestQuery
{

    /**
     * @ApiModelProperty(value="名称")
     */
    public ?string $name;

    /**
     * @ApiModelProperty(value="销量",required=true)
     */
    public int $num;
}