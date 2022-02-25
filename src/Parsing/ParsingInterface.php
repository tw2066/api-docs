<?php

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Collect\RouteCollect;

interface ParsingInterface
{
    /**
     * @param array $mainInfo
     * @param RouteCollect[] $routes
     * @param array $tags
     * @param array $definitionClass
     * @return array
     */
    public function parsing(array $mainInfo, array $routes, array $tags,array $definitionClass):array;
}