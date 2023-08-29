<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use App\Model\Activity;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Mapper;
use HyperfExample\ApiDocs\DTO\ActivityUser;

#[Dto]
class ActivityResponse
{
//    public function __construct()
//    {
//        dump('...............ActivityResponse..................');
//    }

    #[ApiModelProperty('id')]
    public string $id;

    #[ApiModelProperty('活动名称')]
    #[JSONField('activity_name')]
    public string $activityName;

    #[ApiModelProperty('测试')]
    #[JSONField('foo_bar')]
    public string $fooBar;

    /**
     * @var ActivityUser[]
     */
    public array $activityUser;


    public static function from(?Activity $obj): ?ActivityResponse
    {
        return Mapper::copyProperties($obj, new self());
    }
}
