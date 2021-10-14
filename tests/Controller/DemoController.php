<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Controller;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\DemoFormData;
use HyperfTest\ApiDocs\Request\DemoQuery;
use HyperfTest\ApiDocs\Response\Activity;
use HyperfTest\ApiDocs\Response\ActivityPage;
use HyperfTest\ApiDocs\Response\Contact;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;

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
