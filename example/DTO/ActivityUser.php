<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModel;

#[ApiModel('活动参与用户')]
class ActivityUser
{
    public string $id = '';

    public string $activityId = '';

    public string $activityName = '';

    public ?CaseData $case = null;

    public int $caseCount = 0;
}
