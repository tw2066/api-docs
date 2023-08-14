<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\Controller;

use App\Annotation\TestAnnotation;
use App\Model\Activity;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Annotation\ApiSecurity;
use Hyperf\ApiDocs\DTO\GlobalResponse;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Hyperf\DTO\Annotation\Contracts\RequestHeader;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\PhpType;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfExample\ApiDocs\DTO\Address;
use HyperfExample\ApiDocs\DTO\Header\DemoToken;
use HyperfExample\ApiDocs\DTO\PageQuery;
use HyperfExample\ApiDocs\DTO\Request\DemoBodyRequest;
use HyperfExample\ApiDocs\DTO\Request\DemoFormData;
use HyperfExample\ApiDocs\DTO\Request\DemoQuery;
use HyperfExample\ApiDocs\DTO\Response\ActivityPage;
use HyperfExample\ApiDocs\DTO\Response\ActivityResponse;
use HyperfExample\ApiDocs\DTO\Response\CityResponse;
use HyperfExample\ApiDocs\DTO\Response\CodeResponse;
use HyperfExample\ApiDocs\DTO\Response\Contact;
use HyperfExample\ApiDocs\DTO\Response\Page;
use JetBrains\PhpStorm\Deprecated;

#[Controller(prefix: '/exampleDemo')]
#[Api(tags: 'demo管理', position: 1)]
#[ApiHeader('apiHeader')]
class DemoController
{
    public int $a = 1;
    public array $a2 = [];

    public function __construct(protected RequestInterface $request)
    {
    }

//    #[ApiOperation(summary: '查询测试')]
//    #[GetMapping(path: 'api')]
//    #[ApiHeader(name: 'test', required: true, type: 'string')]
//    #[ApiFormData(name: 'photo', required: true, format: 'binary')]
//    public function api(#[RequestQuery] #[Valid] DemoQuery $request): DemoQuery
//    {
//
//        return $request;
//    }
//
//    #[ApiOperation(summary: '查询测试POST')]
//    #[PostMapping(path: 'api')]
//    #[ApiHeader(name: 'test', required: true, type: 'string')]
////    #[ApiFormData(name: 'photo', required: true, format: 'binary')]
//    public function apiPost(#[RequestQuery] #[Valid] DemoQuery $request): DemoQuery
//    {
//
//        return $request;
//    }
//
//    #[ApiOperation('1:查询')]
//    #[PostMapping(path: 'query')]
//    public function query(#[RequestBody] #[Valid] DemoQuery $request): Contact
//    {
//        dump($request);
//        return new Contact();
//    }
//
//    #[ApiOperation('2:提交body数据和get参数')]
//    #[PutMapping(path: 'add')]
//    public function add(#[RequestBody] #[Valid] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query): ActivityResponse
//    {
//        dump($request);
//        dump($query);
//        return new ActivityResponse();
//    }


//    #[ApiOperation('3:表单提交')]
//    #[PostMapping(path: 'fromData')]
//    #[ApiFormData(name: 'photo', format: 'binary')]
//    #[ApiResponse(new ReturnResponse(new Address()))]
//    public function fromData(#[RequestFormData] DemoFormData $formData): array
//    {
//        $file = $this->request->file('photo');
//        dump($file);
//        var_dump($formData);
//        return [new Address()];
//    }

//    #[PostMapping(path: 'page')]
//    #[ApiResponse(new Page([new City()]))]
//    public function page()
//    {
//        $list = [];
//    }


    #[ApiOperation('4:查询单体记录')]
    #[GetMapping(path: 'find/{id}/and/{in}')]
//    #[ApiHeader('test2')]
    #[ApiResponse(PhpType::BOOL,11)]
    #[ApiResponse(PhpType::INT,12)]
    #[ApiResponse(PhpType::FLOAT,13)]
    #[ApiResponse(PhpType::ARRAY,14)]
    #[ApiResponse(PhpType::OBJECT,15)]
    #[ApiResponse(PhpType::STRING,16)]
    #[ApiResponse([PhpType::BOOL],101)]
    #[ApiResponse([PhpType::INT],102)]
    #[ApiResponse([PhpType::FLOAT],103)]
    #[ApiResponse([PhpType::ARRAY],104)]
    #[ApiResponse([PhpType::OBJECT],105)]
    #[ApiResponse([PhpType::STRING],106)]

    #[ApiResponse([])]
    #[ApiResponse([PhpType::BOOL])]
    #[ApiResponse(Address::class,201)]
    #[ApiResponse(new Address(),202)]
    #[ApiResponse([Address::class],203)]
    #[ApiResponse([PhpType::INT],204)]
    #[ApiResponse([new Address()],205)]
    #[ApiResponse(new Page([new Address()]),206)]
    public function find(int $id, float $in)
    {
        return ['$id' => $id, '$in' => $in];
    }
//
//    #[ApiOperation('5:分页')]
//    #[GetMapping(path: 'page')]
//    #[ApiResponse(new Page([new ActivityResponse()]))]
//    public function page(#[RequestQuery] PageQuery $pageQuery)
//    {
//        $activitys = Activity::with(['activityUser' => function ($query) {
//            /* @var HasOne $query */
//            $query->orderBy('id', 'desc')->with(['case']);
//        }])
//            ->paginate($pageQuery->getSize());
//        $arr = [];
//        foreach ($activitys as $activity) {
//            $arr[] = ActivityResponse::from($activity);
//        }
//        return new Page($arr,$activitys->total());
//    }
//
//    #[ApiOperation('6:更新')]
//    #[PutMapping(path: 'update/{id}')]
//    public function update(int $id): int
//    {
//        return $id;
//    }
//
//    #[ApiOperation('7:删除')]
//    #[DeleteMapping(path: 'delete/{id}')]
//    public function delete(int $id): bool
//    {
//        return true;
//    }
//
//    #[ApiOperation('patch方法')]
//    #[PatchMapping(path: 'patch/{id}')]
//    #[Deprecated]
//    public function patch(int $id): int
//    {
//        return $id;
//    }
//
//    #[ApiOperation('获取请求头')]
//    #[PostMapping(path: 'getHeader/{id}')]
//    #[ApiSecurity]
//    public function getHeader(#[RequestHeader] #[Valid] DemoToken $header): DemoToken
//    {
//        dump($header);
//        return $header;
//    }

//    #[ApiOperation('返回 obj(弃用)')]
//    #[GetMapping(path: 'obj')]
//    #[Deprecated]
//    public function obj(): object
//    {
//        return new Address();
//    }

//    #[ApiOperation('登录', security: false)]
//    #[PostMapping(path: 'login')]
//    public function login(#[RequestBody] #[Valid] DemoBodyRequest $request): DemoBodyRequest
//    {
//        return $request;
//    }
//
//    #[ApiOperation('返回code统一格式:int')]
//    #[PostMapping(path: 'code')]
//    #[ApiResponse(new CodeResponse(PhpType::INT))]
//    public function code()
//    {
//        $data = 1;
//        return new CodeResponse($data);
//    }
    #[ApiOperation('返回code统一格式:int')]
    #[PostMapping(path: 'code2')]
    #[ApiResponse(new CodeResponse([PhpType::INT],[PhpType::ARRAY]))]
//    #[ApiResponse(new CodeResponse([PhpType::INT],[]))]
    public function code2()
    {
        $data = 1;
        return new CodeResponse($data,false);
    }
}
