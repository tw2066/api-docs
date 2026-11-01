<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use HyperfExample\ApiDocs\DTO\Address;

#[ApiModel('联系人信息')]
class Contact
{
    #[ApiModelProperty('名称')]
    public string $name = '';

    #[ApiModelProperty('年龄')]
    public ?int $age = null;

    #[ApiModelProperty('城市')]
    public ?CityResponse $city = null;

    /**
     * @var Address[]
     */
    #[ApiModelProperty('地址')]
    public array $addressArr = [];

    #[ApiModelProperty('数组')]
    private ?array $arr = null;

    public function getArr(): ?array
    {
        return $this->arr;
    }

    public function setArr(?array $arr): void
    {
        $this->arr = $arr;
    }
}
