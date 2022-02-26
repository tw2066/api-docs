<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Collect\RouteCollect;

interface ParsingInterface
{
    /**
     * @param RouteCollect[] $routes
     */
    public function parsing(array $mainInfo, array $routes, array $tags): array;
}
