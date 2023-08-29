<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\DTO\Annotation\ArrayType;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Arr;
use Hyperf\DTO\Annotation\Validation\Between;
use Hyperf\DTO\Annotation\Validation\Email;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Nullable;
use Hyperf\DTO\Annotation\Validation\Numeric;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Validation;
use Hyperf\DTO\Type\PhpType;
use HyperfExample\ApiDocs\DTO\Address;
use HyperfExample\ApiDocs\Enum\StatusEnum;

//#[Dto]
class DemoBodyRequest
{

//    public const IN = ['A', 'B', 'C'];
//
//    public Address $addr1;
//
//    public Address $addr2;
//
    #[ApiModelProperty('地址3')]
    #[Required]
    public Address $addr3;

//    #[ApiModelProperty('int数组')]
//    #[Required]
//    #[ArrayType(SimpleType::INT)]
//    public array $intArr;
//
//    #[ApiModelProperty('demo名称')]
//    public ?string $demoName = 'demo';
//
//    #[ApiModelProperty('枚举')]
////    #[In(DemoBodyRequest::IN)]
//    #[Nullable]
//    public StatusEnum $enum;
//
//    #[ApiModelProperty('价格')]
//    #[Required]
//    public float $price;
//
//    #[ApiModelProperty('电子邮件', example: '1@e.com')]
//    #[Email(messages: '请输入正确的电子邮件')]
//    public string $email;
//
//    /**
//     * @var int[]
//     */
//    #[ApiModelProperty('int数据')]
//    #[Validation(rule: 'array')]
//    public array $intIdList;
//
//    #[ApiModelProperty('addArr')]
//    #[Validation(rule: 'array')]
//    #[ArrayType(Address::class)]
//    public array $addArr;
//
//    public object $obj;
//
//    #[Integer]
//    #[Between(min: 2, max: 10)]
//    public int $num = 6;

    #[Validation('required|numeric')]
    #[Integer]
    #[JSONField('test_name')]
    public int $test=1;

//    #[Between(min: 2, max: 10)]
    #[ApiModelProperty('intArr数组')]
    #[Arr]
    #[Validation('integer', customKey: 'intArr.*')]
    public array $intArr;



    private int $privateName = 0;

}
