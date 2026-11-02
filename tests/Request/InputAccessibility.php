<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class InputAccessibility
{
    public static string $static = 'static';

    public readonly string $id;

    public string $name = '';

    public function __construct()
    {
        $this->id = '1';
    }
}
