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
namespace HyperfExample\ApiDocs\DTO;

class ActivityUser
{
    public string $id;

    public string $activityId;

    public string $activityName;

    public ?CaseData $case;

    public int $caseCount = 0;

    public function setCase(?CaseData $case): void
    {
        $this->case = $case;
    }
}
