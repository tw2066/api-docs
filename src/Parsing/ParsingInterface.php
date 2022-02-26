<?php

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Collect\RouteCollect;

interface ParsingInterface
{
    /**
     * @param array $mainInfo
     * @param RouteCollect[] $routes
     * @param array $tags
     * @return array
     */
    public function parsing(array $mainInfo, array $routes, array $tags):array;
}