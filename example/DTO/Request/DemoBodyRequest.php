<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\DTO\Address;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Email;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Validation;
use Hyperf\DTO\Contracts\RequestBody;

class DemoBodyRequest implements RequestBody
{
    /**
     * @ApiModelProperty(value="demo名称")
     */
    public ?string $demoName = null;

    /**
     * @ApiModelProperty(value="价格")
     * @Required
     */
    public float $price;

    /**
     * @ApiModelProperty(value="电子邮件", example="1@e.com")
     * @Email(messages="请输入正确的电子邮件")
     */
    public string $email;

    /**
     * @ApiModelProperty(value="电子邮件2", example="2@q.com")
     * @Validation(rule="required")
     * @Validation(rule="email", messages="请输入正确的电子邮件")
     */
    public string $email2;

    /**
     * @ApiModelProperty(value="示例id",required=true)
     * @Validation(rule="array")
     * @var int[]
     */
    public array $demoId;

    /**
     * @ApiModelProperty(value="地址数组")
     * @Required()
     * @var \App\DTO\Address[]
     */
    public array $addrArr;

    /**
     * @ApiModelProperty(value="地址")
     * @Required
     */
    public Address $addr;

    /**
     * @ApiModelProperty(value="地址数组",required=true)
     * @Validation(rule="array",messages="必须为数组")
     */
    public array $addr2;
}
