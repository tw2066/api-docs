<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Arr;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Validation;
use HyperfExample\ApiDocs\DTO\Address;
use HyperfExample\ApiDocs\DTO\SystemRequest;

#[ApiModel('DemoBody请求体')]
class DemoBodyRequest
{
    use SystemRequest;

    #[ApiModelProperty('收货地址')]
    #[Required]
    public Address $shippingAddress;

    #[ApiModelProperty(value: '商品数量', example: 1)]
    #[Validation('required|numeric')]
    #[Integer]
    #[JSONField('item_count')]
    public int $quantity = 1;

    #[ApiModelProperty('intArr数组', example: [1, 2])]
    #[Arr]
    #[Validation('integer', customKey: 'intArr.*')]
    public array $intArr = [];
}
