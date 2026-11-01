<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\ArrayType;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;

#[ApiModel('地址')]
class Address
{
    #[ApiModelProperty('街道')]
    public string $street = '';

    #[ApiModelProperty('纬度')]
    public float $latitude = 0.0;

    #[ApiModelProperty('城市')]
    #[Required]
    public ?City $city = null;

    #[ApiModelProperty('城市数组')]
    #[ArrayType(City::class)]
    #[JSONField('city_new')]
    public array $cityArr = [];
}
