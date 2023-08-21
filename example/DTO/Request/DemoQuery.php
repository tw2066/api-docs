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

#[HyperfData]
#[Data]
#[Dto]
class DemoQuery
{

    #[ApiModelProperty('这是一个测试')]
    #[JSONField('alias66666')]
    #[Required]
    public int $test123456 = 123;

    #[ApiModelProperty('类型')]
    #[In(['a','b'])]
    private string $type;

    private PhpType $phpType;
    #[AsyncAnnotation]
    public function getA()
    {
        return $this->test123456;
    }

}
