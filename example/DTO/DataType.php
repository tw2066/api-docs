<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Type\Convert;

#[Dto(Convert::SNAKE)]
#[ApiModel('类型推导示例')]
class DataType
{
    public $noTypeName;

    public int $intName = 1;

    public string $stringName = '';

    public bool $boolName = false;

    public float $floatName = 0.0;

    public mixed $mixedName = null;

    public object $objectName;

    public City $cityName;

    public ?City $cityNameNull = null;
}
