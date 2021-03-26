<?php


namespace App\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class Address
{

    public string $street;

    /**
     * @ApiModelProperty(value="城市")
     */
    public string $city;

}