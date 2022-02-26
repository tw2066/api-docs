<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use App\Model\Activity;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Mapper;

class ActivityResponse
{
    #[ApiModelProperty('id')]
    public string $id;

    #[ApiModelProperty('活动名称')]
    public string $activityName;

    /**
     * @var \HyperfExample\ApiDocs\DTO\ActivityUser[]
     */
    public array $activityUser;

    public static function from(?Activity $obj): ?ActivityResponse
    {
        return Mapper::copyProperties($obj, new self());
    }
}
