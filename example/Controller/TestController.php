<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\Controller;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use HyperfExample\ApiDocs\DTO\Request\DemoQuery;
use HyperfExample\ApiDocs\DTO\Response\Contact;

#[Controller(prefix: '/exampleTest')]
#[Api(tags: '测试管理控制器', position: 2)]
#[ApiHeader('testHeader')]
class TestController
{
    #[ApiOperation('查询', security: false)]
    #[PostMapping(path: 'query')]
    public function query(#[RequestBody] #[Valid] DemoQuery $request): Contact
    {
        var_dump($request);
        return new Contact();
    }
}
