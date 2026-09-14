<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\Contract\Arrayable;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Type\PhpType;

// #[HyperfData]
// #[Data]
class DemoQuery
{
    #[ApiModelProperty('这是一个别名属性')]
    #[JSONField('alias_name')]
    #[Required]
    public string $aliasName;

    #[ApiModelProperty('类型')]
    #[In(['a', 'b'])]
    public string $type;



}
