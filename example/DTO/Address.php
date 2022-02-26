<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Required;

class Address
{
    public string $street;

    #[ApiModelProperty('浮点数')]
    public float $float;

    #[ApiModelProperty('城市')]
    #[Required]
    public ?City $city = null;
}
