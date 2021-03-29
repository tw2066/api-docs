<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\DTO\Request\DemoBodyRequest;
use App\DTO\Request\DemoFormData;
use App\DTO\Request\DemoQuery;
use App\DTO\Response\Contact;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiOperation;

/**
 * @Controller(prefix="/demo")
 * @Api(tags="demo管理",position=1)
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
     * @ApiOperation(summary="查询单条记录",position=1)
     * @GetMapping(path="find/{id}/and/{in}")
     */
    public function find(int $id,float $in): array
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
     * @ApiFormData(name="fileAddr",type="file")
     * @ApiResponse(code="404",description="Not Found")
     * @PostMapping(path="fromData")
     */
    public function fromData(DemoFormData $formData): bool
    {
        return true;
    }
}
