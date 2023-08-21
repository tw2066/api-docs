<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use App\Annotation\AsyncAnnotation;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Type\PhpType;
use Hyperf\PhpAccessor\Annotation\HyperfData;
use HyperfExample\ApiDocs\DTO\City;
use PhpAccessor\Attribute\Data;

//#[HyperfData]
//#[Data]
#[Dto]
class DemoQuery
{

    #[ApiModelProperty('这是一个别名属性')]
    #[JSONField('alias_name')]
    #[Required]
    public string $aliasName;

    #[ApiModelProperty('类型')]
    #[In(['a','b'])]
    private string $type;

    private PhpType $phpType;
    //#[AsyncAnnotation]
    public function getA()
    {
        return $this->test123456;
    }

}
