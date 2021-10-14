<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Controller;

use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\DemoQuery;
use HyperfTest\ApiDocs\Response\Activity;

#[Controller(prefix: '/demo')]
class DemoController
{
    #[ApiOperation('添加方法')]
    #[PostMapping(path: 'add')]
    public function add(#[RequestBody] #[Valid] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query): Activity
    {
        return new Activity();
    }
}
