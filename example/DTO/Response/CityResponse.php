<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Type\Convert;

#[Dto(Convert::SNAKE)]
#[ApiModel('城市响应')]
class CityResponse
{
    #[ApiModelProperty('名称')]
    public string $name = '';

    public array $bodyName = [];

    #[JSONField('name_list')]
    #[Required]
    private array $nameArr = [];
}
