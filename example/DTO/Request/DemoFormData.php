<?php

namespace App\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Contracts\RequestFormData;

class DemoFormData implements RequestFormData
{

    /**
     * @ApiModelProperty(value="名称",required=true)
     */
    public string $name;

    /**
     * @ApiModelProperty(value="数量",required=true)
     * @Integer()
     * @Between(min=2,max=10)
     */
    public int $num;

}