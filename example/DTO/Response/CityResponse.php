<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Type\Convert;

//#[Dto(Convert::SNAKE)]
class CityResponse
{
    public string $name;

    #[JSONField('name_arr_1')]
    private array $nameArr = [];

    public array $bodyName = [];

    /**
     * @return array
     */
    public function getBodyName(): array
    {
        return $this->bodyName;
    }

}
