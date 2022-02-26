<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

use Hyperf\DTO\Scan\Property;

class ParameterInfo
{
    public string $name;

    public string $in;

    public ?string $description = null;

    public ?bool $required = null;

    public ?string $type = null;

    public mixed $default = null;

    public mixed $example = null;

    public bool $hidden = false;

    public ?array $enum = null;

    public ?Property $property = null;
}
