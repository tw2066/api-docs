<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\Controller;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Annotation\ApiSecurity;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Hyperf\DTO\Annotation\Contracts\RequestHeader;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\Type\PhpType;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use HyperfExample\ApiDocs\DTO\Address;
use HyperfExample\ApiDocs\DTO\DataType;
use HyperfExample\ApiDocs\DTO\Header\DemoToken;
use HyperfExample\ApiDocs\DTO\PageQuery;
use HyperfExample\ApiDocs\DTO\Request\DemoBodyRequest;
use HyperfExample\ApiDocs\DTO\Request\DemoDatabaseRequest;
use HyperfExample\ApiDocs\DTO\Request\DemoFormData;
use HyperfExample\ApiDocs\DTO\Request\DemoQuery;
use HyperfExample\ApiDocs\DTO\Response\ActivityResponse;
use HyperfExample\ApiDocs\DTO\Response\CityResponse;
use HyperfExample\ApiDocs\DTO\Response\CodeResponse;
use HyperfExample\ApiDocs\DTO\Response\Contact;
use HyperfExample\ApiDocs\DTO\Response\Page;
use HyperfExample\ApiDocs\DTO\Response\ReadonlyResponse;

#[Controller(prefix: '/exampleDemo')]
#[Api(tags: 'demo管理', position: 1)]
#[ApiHeader(name: 'apiHeader')]
class DemoController
{
    #[ApiOperation(summary: '查询测试')]
    #[GetMapping(path: 'detail')]
    public function detail(#[RequestQuery] #[Valid] DemoQuery $request): DataType
    {
        return new DataType();
    }

    /**
     * @param DemoQuery[] $request
     */
    #[ApiOperation(summary: '查询测试POST Arr')]
    #[PostMapping(path: 'list')]
    public function list(#[RequestBody] #[Valid] array $request): array
    {
        return $request;
    }

    #[ApiOperation(summary: '查询测试POST')]
    #[PostMapping(path: 'create')]
    #[ApiHeader(name: 'test', required: true, type: 'string')]
    public function create(#[RequestBody] #[Valid] DemoQuery $request): DemoQuery
    {
        return $request;
    }

    #[ApiOperation(summary: '1:查询')]
    #[PostMapping(path: 'search')]
    public function search(#[RequestBody] #[Valid] DemoQuery $request): Contact
    {
        return new Contact();
    }

    #[ApiOperation(summary: '2:提交body数据和get参数')]
    #[PutMapping(path: 'save')]
    public function save(#[RequestBody] #[Valid] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query): ActivityResponse
    {
        return new ActivityResponse();
    }

    /**
     * @return Address[]
     */
    #[ApiOperation(summary: '3:表单提交')]
    #[PostMapping(path: 'formData')]
    #[ApiFormData(name: 'photo', format: 'binary')]
    public function formData(#[RequestFormData] DemoFormData $formData): array
    {
        return [new Address()];
    }

    #[ApiOperation(summary: '4:查询单条记录')]
    #[GetMapping(path: 'find/{id}/rate/{rate}')]
    #[ApiHeader(name: 'test2')]
    #[ApiResponse([PhpType::BOOL])]
    #[ApiResponse(Address::class, 201)]
    #[ApiResponse([Address::class], 203)]
    #[ApiResponse([PhpType::INT], 204)]
    #[ApiResponse(new Page([Address::class]), 206)]
    public function find(int $id, float $rate): array
    {
        return ['id' => $id, 'rate' => $rate];
    }

    #[ApiOperation(summary: '5:分页')]
    #[GetMapping(path: 'page')]
    #[ApiResponse(new Page([new ActivityResponse()]))]
    public function page(#[RequestQuery] PageQuery $pageQuery): Page
    {
        $activity = new ActivityResponse();
        $activity->id = '1';
        $activity->activityName = '示例活动';

        return new Page([$activity], 1);
    }

    #[ApiOperation(summary: '6:更新')]
    #[PutMapping(path: 'update/{id}')]
    public function update(int $id): int
    {
        return $id;
    }

    #[ApiOperation(summary: '7:删除')]
    #[DeleteMapping(path: 'delete/{id}')]
    public function delete(int $id): bool
    {
        return true;
    }

    #[ApiOperation(summary: 'patch方法')]
    #[PatchMapping(path: 'patch/{id}')]
    public function patch(int $id): int
    {
        return $id;
    }

    #[ApiOperation(summary: '获取请求头')]
    #[PostMapping(path: 'header/{id}')]
    #[ApiSecurity]
    public function header(#[RequestHeader] #[Valid] DemoToken $header): void {}

    #[ApiOperation(summary: '返回 obj(弃用)', deprecated: true)]
    #[GetMapping(path: 'obj')]
    public function obj(): object
    {
        return new Address();
    }

    #[ApiOperation(summary: '登录', security: false)]
    #[PostMapping(path: 'login')]
    public function login(#[RequestBody] #[Valid] DemoBodyRequest $request): DemoBodyRequest
    {
        return $request;
    }

    #[ApiOperation(summary: '返回code统一格式:int')]
    #[PostMapping(path: 'codeInt')]
    #[ApiResponse(new CodeResponse(PhpType::INT))]
    public function codeInt(): CodeResponse
    {
        return new CodeResponse(1);
    }

    #[ApiOperation(summary: '返回code统一格式:int+数组')]
    #[PostMapping(path: 'codeIntArr')]
    #[ApiResponse(new CodeResponse([PhpType::INT], [CityResponse::class]))]
    public function codeIntArr(): CodeResponse
    {
        $cityResponse = new CityResponse();
        $cityResponse->name = '小明';

        return new CodeResponse(1, [$cityResponse]);
    }

    #[ApiOperation(summary: 'Dto 字段转换')]
    #[PostMapping(path: 'city')]
    public function city(): CityResponse
    {
        $city = new CityResponse();
        $city->name = 'name2';
        $city->bodyName = [1, 2, 3];

        return $city;
    }

    #[ApiOperation(summary: '数据库验证')]
    #[PostMapping(path: 'validateDatabase')]
    public function validateDatabase(#[RequestBody] #[Valid] DemoDatabaseRequest $request): int
    {
        return 1;
    }

    #[ApiOperation(summary: '只读响应字段')]
    #[GetMapping(path: 'readonly')]
    public function readonly(): ReadonlyResponse
    {
        return new ReadonlyResponse('1', date('Y-m-d H:i:s'));
    }
}
