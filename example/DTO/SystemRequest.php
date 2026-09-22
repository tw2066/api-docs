<?php

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Arr;
use Hyperf\DTO\Annotation\Validation\Validation;

trait SystemRequest
{

    /**
     * @var int[]
     */
    #[ApiModelProperty('系统标识',example: [1])]
    #[Arr]
    #[Validation('integer', customKey: 'system.*')]
    public array $system = [];

}