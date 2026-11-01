<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\Model;

class Activity
{
    public string $id = '';

    public string $activityName = '';

    public string $location = '';

    /**
     * @var array<int, mixed>
     */
    public array $activityUser = [];
}
