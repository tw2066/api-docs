<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Mapper;
use HyperfExample\ApiDocs\DTO\ActivityUser;
use HyperfExample\ApiDocs\Model\Activity;

#[Dto]
#[ApiModel('活动响应')]
class ActivityResponse
{
    #[ApiModelProperty('id')]
    public string $id = '';

    #[ApiModelProperty('活动名称')]
    #[JSONField('activity_name')]
    public string $activityName = '';

    #[ApiModelProperty('活动地点')]
    #[JSONField('location_name')]
    public string $location = '';

    /**
     * @var ActivityUser[]
     */
    #[ApiModelProperty('参与用户')]
    public array $activityUser = [];

    public static function from(?Activity $obj): ?self
    {
        return Mapper::copyProperties($obj, new self());
    }
}
