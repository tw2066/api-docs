<?php

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\DTO\Annotation\Dto;

#[Dto]
class DataType
{
    public int $intName;
    public string $stringName;
    public bool $boolName;
    public float $floatName;
    public mixed $mixedName;
    public ?object $objectName;
    public ?City $cityName;

}