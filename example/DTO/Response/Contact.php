<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class Contact
{
    #[ApiModelProperty('名称')]
    public string $name;

    #[ApiModelProperty('年龄')]
    public ?int $age = null;

    #[ApiModelProperty('城市')]
    public ?City $city = null;

    #[ApiModelProperty('城市1')]
    public ?City1 $city1 = null;

    /**
     * 需要绝对路径.
     * @var \HyperfExample\ApiDocs\DTO\Address[]
     */
    #[ApiModelProperty('地址')]
    public array $addressArr;

    #[ApiModelProperty('数组')]
    private ?array $arr;

    public function getArr(): ?array
    {
        return $this->arr;
    }

    public function setArr(?array $arr): void
    {
        $this->arr = $arr;
    }
}
