<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Request\DemoBodyRequest;
use App\DTO\Request\DemoFormData;
use App\DTO\Request\DemoQuery;
use App\DTO\Response\Contact;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiOperation;

/**
 * @Controller(prefix="/demo")
 * @Api(tag="demo管理")
 */
class DemoController
{
    /**
     * @ApiOperation(summary="查询")
     * @PostMapping(path="index")
     */
    public function index(DemoQuery $request): Contact
    {
        $contact = new Contact();
        var_dump($request);
        return $contact;
    }

    /**
     * @ApiOperation(summary="查询单条记录")
     * @GetMapping(path="find/{id}/and/{in}")
     */
    public function find(int $id, int $in): array
    {
        return ['$id' => $id, '$in' => $in];
    }

    /**
     * @ApiOperation(summary="提交body数据和get参数")
     * @PostMapping(path="add")
     */
    public function add(DemoBodyRequest $request, DemoQuery $request2): Contact
    {
        var_dump($request2);
        var_dump($request);
        return new Contact();
    }

    /**
     * @ApiOperation(summary="表单提交")
     * @PostMapping(path="fromData")
     */
    public function fromData(DemoFormData $formData): bool
    {
        var_dump($formData);
        return true;
    }
}
