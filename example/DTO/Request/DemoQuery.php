<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;

#[Dto]
class DemoQuery
{

    #[ApiModelProperty('这是一个测试')]
    #[JSONField('alias66666')]
    #[Required]
    public int $test123456 = 123;

//    public function setAlias66666(int $alias66666)
//    {
//        $this->alias66666 = $alias66666;
//        $this->test123456 = $alias66666;
//        return $this;
//    }

}
