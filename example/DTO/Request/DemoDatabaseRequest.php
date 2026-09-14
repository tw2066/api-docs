<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use App\Model\Sale;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Exists;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Unique;

class DemoDatabaseRequest
{
    #[ApiModelProperty('这是一个Exists验证')]
    #[Required]
    #[Exists(Sale::class, 'id')]
    public int $id = 1;

    #[ApiModelProperty('这是一个Exists验证 product')]
    #[Required]
    #[Exists(Sale::class)]
    public float $product = 1.1;

    #[ApiModelProperty('这是一个Unique验证')]
    #[Required]
    #[Unique(Sale::class,'product','id')]
    public float $uniqueProduct = 1.3;
}
