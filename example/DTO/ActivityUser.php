<?php

declare(strict_types=1);

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
