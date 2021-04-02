<?php
/**
 * Created by PhpStorm.
 * User: Tw
 * Date: 2021/2/24 0024
 * Time: 13:55
 */

namespace App\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Numeric;

class Address
{

    public string $street;

    /**
     * @ApiModelProperty(value="城市")
     * @Numeric()
     */
    public float $city;

    /**
     * @ApiModelProperty(value="地址2")
     */
    public ?Address2 $address2 = null;


}