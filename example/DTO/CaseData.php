<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModel;

#[ApiModel('活动案例')]
class CaseData
{
    public string $id = '';

    public string $caseTitle = '';
}
