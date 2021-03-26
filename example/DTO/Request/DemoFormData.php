<?php

namespace App\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Contracts\RequestFormData;

class DemoFormData implements RequestFormData
{

    /**
     * @ApiModelProperty(value="名称",required=true)
     */
    public string $name;

    /**
     * @ApiModelProperty(value="数量",required=true)
     */
    public int $num;

}