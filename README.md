## PHP Swagger Api Docs
 基于 [Hyperf](https://github.com/hyperf/hyperf) 框架的 swagger 文档生成组件

##### 优点
- 声明参数类型完成自动注入，参数映射到PHP类，根据类和注解自动生成Swagger文档
- 代码可维护性好，扩展性好 
- 支持数组，递归，嵌套和数据校验
- 支持api token认证

##### 缺点
- 模型类需要手工编写

## 安装

```
composer require tangwei/apidocs
```
## 使用

#### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish tangwei/apidocs
```
> config/autoload/apidocs.php
```php
return [
    // enable false 将不会启动 swagger 服务
    'enable' => env('APP_ENV') !== 'prod',
    'output_dir' => BASE_PATH . '/runtime/swagger',
    //认证api key
    //'security_api_key' => ['Authorization'],
    //全局responses
    'responses' => [
        401=>['description'=>'Unauthorized']
    ],
    // swagger 的基础配置
    'swagger' => [
        'swagger' => '2.0',
        'info' => [
            'description' => 'swagger api desc',
            'version' => '1.0.0',
            'title' => 'API DOC',
        ],
        'host' => '',
        'schemes' => [],
    ],
];
```

### 2. 直接启动框架(需要有http服务)
```shell script
php bin/hyperf.php start

[INFO] Swagger Url at 0.0.0.0:9531/swagger
[INFO] TaskWorker#1 started.
[INFO] Worker#0 started.
[INFO] HTTP Server listening at 0.0.0.0:9531
```
> 看到`Swagger Url`显示，表示文档生成成功，访问`/swagger`即可以看到swagger页面


### 3. 使用

## 组件契约
> 定义一个类，增加类型属性 实现`implements`对应的接口即可
> 命名空间:`Hyperf\DTO\Contracts`
#### RequestBody
- Body参数
```php
class DemoBodyRequest implements RequestBody{}
```
### RequestQuery
- GET参数
```php
class DemoQuery implements RequestQuery{}
```
### RequestFormData
- 表单请求
```php
class GoodsFormData implements RequestFormData{}
```
> 注意: 一个方法中，不要同时注入RequestBody和RequestFormData

## 示例
### 控制器
```php
<?php

declare(strict_types=1);

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
use Hyperf\HttpServer\Annotation\PutMapping;

/**
 * @Controller(prefix="/demo")
 * @Api(tags="demo管理",position=1)
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
    public function find(int $id,float $in): array
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
        return json_encode($request,JSON_UNESCAPED_UNICODE);
    }

    /**
     * @ApiOperation(summary="表单提交")
     * @ApiFormData(name="photo",type="file")
     * @ApiResponse(code="404",description="Not Found")
     * @PostMapping(path="fromData")
     */
    public function fromData(DemoFormData $formData): bool
    {
        //文件上传
        $file = $this->request->file('photo');
        var_dump($file);
        var_dump($formData);
        return true;
    }
}
```
### 数据传输对象(DTO类)
```php
<?php

namespace App\DTO\Request;

use App\DTO\Address;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Email;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Validation;
use Hyperf\DTO\Contracts\RequestBody;

class DemoBodyRequest implements RequestBody
{

    /**
     * @ApiModelProperty(value="demo名称")
     */
    public ?string $demoName = null;

    /**
     * @ApiModelProperty(value="价格")
     * @Required()
     */
    public float $price;
    /**
     * @ApiModelProperty(value="电子邮件",example="1@qq.com")
     * @Required()
     * @Email(messages="请输入正确的电子邮件")
     * @var string
     */
    public string $email;
    /**
     * @ApiModelProperty(value="示例id",required=true)
     * @Validation(rule="array")
     * @var int[]
     */
    public array $demoId;
    /**
     * @ApiModelProperty(value="地址数组")
     * @Required()
     * @var \App\DTO\Address[]
     */
    public array $addrArr;
    /**
     * @ApiModelProperty(value="地址")
     * @Required()
     */
    public Address $addr;


    /**
     * @ApiModelProperty(value="地址数组",required=true)
     * @Validation(rule="array",messages="必须为数组")
     */
    public array $addr2;

}
```
## 验证器
### 基于框架的验证
> 安装hyperf框架验证器[hyperf/validation](https://github.com/hyperf/validation), 并配置(已安装忽略)
- 注解
`@Required` `@Between` `@Date` `@Email` `@Image` `@Integer` `@Nullable` `@Numeric`  `@Url` `@Validation`

```php
    /**
     * @ApiModelProperty(value="电子邮件",example="1@qq.com")
     * @Required()
     * @Email(messages="请输入正确的电子邮件")
     * @var string
     */
    public string $email;

    /**
     * @ApiModelProperty(value="电子邮件",example="1@qq.com")
     * @Validation(rule="required")
     * @Validation(rule="email",messages="请输入正确的电子邮件")
     * @var string
     */
    public string $email2;
```
- @Validation
> rule 支持框架所有验证
- 自定义验证注解
> 只需继承`Hyperf\DTO\Annotation\Validation\BaseValidation`即可
```php
/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Image extends BaseValidation
{
    public $rule = 'image';
}
```
  
> 其他例子，请查看example
## 注意
```php
    /**
     * @ApiModelProperty(value="地址数组")
     * @Required()
     * @var \App\DTO\Address[]
     */
    public array $addrArr;
```
- 映射数组类时,`@var`需要写绝对路径
- 控制器中使用了框架`AutoController`注解,只收集了`POST`方法
## Swagger界面
![hMvJnQ](https://gitee.com/tw666/source/raw/master/img/swagger.png)



