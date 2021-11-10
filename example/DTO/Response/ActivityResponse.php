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
