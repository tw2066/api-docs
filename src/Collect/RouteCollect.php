<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

class RouteCollect
{
    public string $route;

    /**
     * 请求方法.
     */
    public string $requestMethod;

    /**
     * 简单类名.
     */
    public string $simpleClassName;

    /**
     * 位置.
     */
    public int $position;

    public string $summary = '';

    public string $description = '';

    public string $operationId = '';

    public bool $deprecated = false;

    /**
     * @var string[]
     */
    public array $tags = [];

    /**
     * @var ParameterInfo[]
     */
    public array $parameters = [];

    /**
     * @var ResponseInfo[]
     */
    public array $responses = [];

    public array $consumeTypes = [];

    public array $produces = [];

    public array $security = [];
}
