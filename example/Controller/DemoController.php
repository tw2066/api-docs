<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Request\DemoBodyRequest;
use App\DTO\Request\DemoFormData;
use App\DTO\Request\DemoQuery;
use App\DTO\Response\Contact;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;

/**
 * @Controller(prefix="/demo")
 * @Api(tags="demo管理", position=1)
 */
class DemoController extends AbstractController
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
    public function find(int $id, float $in): array
    {
        return ['$id' => $id, '$in' => $in];
    }

    /**
     * @ApiOperation(summary="提交body数据和get参数")
     * @PutMapping(path="add")
     */
    public function add(DemoBodyRequest $request, DemoQuery $request2)
    {
        var_dump($request2);
        return json_encode($request, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @ApiOperation(summary="表单提交")
     * @ApiFormData(name="photo", type="file")
     * @ApiResponse(code="404", description="Not Found")
     * @PostMapping(path="fromData")
     */
    public function fromData(DemoFormData $formData): bool
    {
        $file = $this->request->file('photo');
        var_dump($file);
        var_dump($formData);
        return true;
    }
}
