<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;
use Hyperf\DTO\Annotation\Validation\Email;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Nullable;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Validation;
use HyperfExample\ApiDocs\DTO;
use HyperfExample\ApiDocs\DTO\Address;

class DemoBodyRequest
{
    public const IN = ['A', 'B', 'C'];

    public Address $addr1;

    public DTO\Address $addr2;

    #[ApiModelProperty('地址')]

    #[Required]
    public \HyperfExample\ApiDocs\DTO\Address $addr3;

    /**
     * @var \HyperfExample\ApiDocs\DTO\Address[]
     */
    #[ApiModelProperty('地址数组')]
    #[Required]
    public array $addrArr;

    #[ApiModelProperty('demo名称')]
    public ?string $demoName = 'demo';

    #[ApiModelProperty('枚举')]
    #[In(DemoBodyRequest::IN)]

    #[Nullable]
    #[Required]
    public ?string $enum;

    #[ApiModelProperty('价格')]

    #[Required]
    public float $price;

    #[ApiModelProperty('电子邮件', example: '1@e.com')]
    #[Email(messages: '请输入正确的电子邮件')]
    public string $email;

    /**
     * @var int[]
     */
    #[ApiModelProperty('int数据')]
    #[Validation(rule: 'array')]
    public array $intIdList;

    public object $obj;

    #[Integer]
    #[Between(min: 2, max: 10)]
    public int $num = 5;
}
