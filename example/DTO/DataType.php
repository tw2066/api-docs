<?php

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Type\Convert;

#[Dto(Convert::SNAKE)]
class DataType
{
    public  $noTypeName;
    public int $intName = 1;
    public string $stringName;
    public bool $boolName;
    public float $floatName;
    public mixed $mixedName;
    public object $objectName;
    public ?object $objectNameNull;
    public City $cityName;
    public City $cityIntName;
    public ?City $cityNameNull = null;

}