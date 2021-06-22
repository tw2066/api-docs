<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Numeric;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Contracts\RequestQuery;

class DemoQuery implements RequestQuery
{
    /**
     * @ApiModelProperty(value="名称")
     * @Required
     * @Numeric
     */
    public string $name;

    /**
     * @ApiModelProperty(value="销量", required=true)
     */
    public int $num;
}
