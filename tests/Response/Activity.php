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

namespace HyperfTest\ApiDocs\Response;

use App\Model\CmActivity;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Mapper;

class Activity
{

    public string $id;


    public string $activityName;

//    /**
//     * @var \HyperfTest\DTO\DemoQuery[]
//     */
//    public array $activityUser;

    public static function from(?CmActivity $obj): ?Activity
    {
        return Mapper::copy($obj, new self());
        //$toObj->activityUser = [new ActivityUser()];
    }
}
