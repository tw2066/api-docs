<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

class RouteCollect
{

    //deprecated: false
    //description: ""
    //operationId: "ExampleDemoAddPUT"
    //parameters: [{in: "header", name: "apiHeader", type: "string"},…]
    //produces: ["application/json"]
    //responses: {200: {schema: {$ref: "#/definitions/ActivityResponse"}, description: "OK"},…}
    //security: [{Authorization: []}, {token: []}]
    //summary: "提交body数据和get参数"
    //tags: ["demo管理"]

    public string $route;

    /**
     * 请求方法
     * @var string
     */
    public string $requestMethod;

    /**
     * 简单类名
     * @var string
     */
    public string $simpleClassName;

    /**
     * 位置
     * @var int
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

    public array $consumes = [];

    public array $produces = [];

    public array $security = [];




}
