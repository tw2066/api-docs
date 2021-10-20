## PHP Swagger Api Docs
 基于 [Hyperf](https://github.com/hyperf/hyperf) 框架的 swagger 文档生成组件

##### 优点

- 声明参数类型完成自动注入，参数映射到PHP类，根据类和注解自动生成Swagger文档
- 代码可维护性好，扩展性好
- 支持数组，递归，嵌套和数据校验
- 支持api token认证
- 支持PHP8原生注解

## 使用须知

* php >= 8.0
* 控制器中方法尽可能返回类,这样会更好的生成文档
* 当返回类的结果满足不了时,用 #[ApiResponse] 注解
* 模型类需要手工编写

## 安装

```
composer require tangwei/apidocs
```

## 使用

#### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish tangwei/apidocs
```
##### 1.1 配置信息
> config/autoload/api_docs.php
```php
return [
    // enable false 将不会启动 swagger 服务
    'enable' => env('APP_ENV') !== 'prod',
    'output_dir' => BASE_PATH . '/runtime/swagger',
    //认证api key
    'security_api_key' => ['Authorization'],
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

[INFO] Swagger Url at 0.0.0.0:9501/swagger
[INFO] TaskWorker#1 started.
[INFO] Worker#0 started.
[INFO] HTTP Server listening at 0.0.0.0:9501
```

> 看到`Swagger Url`显示，表示文档生成成功，访问`/swagger`即可以看到swagger页面

### 3. 使用

## 注解

> 命名空间:`Hyperf\DTO\Annotation\Contracts`

#### RequestBody

- 获取Body参数

```php
public function add(#[RequestBody] DemoBodyRequest $request){}
```

### RequestQuery

- 获取GET参数

```php
public function add(#[RequestQuery] DemoQuery $request){}
```

### RequestFormData

- 获取表单请求

```php
public function fromData(#[RequestFormData] DemoFormData $formData){}
```

- 获取文件(和表单一起使用)

```php
#[ApiFormData(name: 'photo', type: 'file')]
```

- 获取Body参数和GET参数

```php
public function add(#[RequestBody] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query){}
```

> 注意: 一个方法，不能同时注入RequestBody和RequestFormData

## 示例

### 控制器

```php
#[Controller(prefix: '/demo')]
#[Api(tags: 'demo管理', position: 1)]
class DemoController extends AbstractController
{
    #[ApiOperation(summary: '查询')]
    #[PostMapping(path: 'index')]
    public function index(#[RequestQuery] #[Valid] DemoQuery $request): Contact
    {
        $contact = new Contact();
        $contact->name = $request->name;
        var_dump($request);
        return $contact;
    }

    #[PutMapping(path: 'add')]
    #[ApiOperation(summary: '提交body数据和get参数')]
    public function add(#[RequestBody] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query)
    {
        var_dump($query);
        return json_encode($request, JSON_UNESCAPED_UNICODE);
    }

    #[PostMapping(path: 'fromData')]
    #[ApiOperation(summary: '表单提交')]
    #[ApiFormData(name: 'photo', type: 'file')]
    #[ApiResponse(code: '200', description: 'success', className: Address::class, type: 'array')]
    public function fromData(#[RequestFormData] DemoFormData $formData): bool
    {
        $file = $this->request->file('photo');
        var_dump($file);
        var_dump($formData);
        return true;
    }

    #[GetMapping(path: 'find/{id}/and/{in}')]
    #[ApiOperation('查询单体记录')]
    #[ApiHeader(name: 'test')]
    public function find(int $id, float $in): array
    {
        return ['$id' => $id, '$in' => $in];
    }

}

```

## 验证器

### 基于框架的验证

> 安装hyperf框架验证器[hyperf/validation](https://github.com/hyperf/validation), 并配置(已安装忽略)

- 注解
  `Required` `Between` `Date` `Email` `Image` `Integer` `Nullable` `Numeric`  `Url` `Validation` `...`
- 校验生效

> 只需在控制器方法中加上 #[Valid] 注解

```php
public function index(#[RequestQuery] #[Valid] DemoQuery $request){}
```

```php
class DemoQuery
{
    #[ApiModelProperty('名称')]
    #[Required]
    #[Max(5)]
    #[In(['qq','aa'])]
    public string $name;

    #[ApiModelProperty('正则')]
    #[Str]
    #[Regex('/^.+@.+$/i')]
    #[StartsWith('aa,bb')]
    #[Max(10)]
    public string $email;

    #[ApiModelProperty('数量')]
    #[Required]
    #[Integer]
    #[Between(1,5)]
    public int $num;
}
```

- Validation

> rule 支持框架所有验证
- 自定义验证注解
> 只需继承`Hyperf\DTO\Annotation\Validation\BaseValidation`即可
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Image extends BaseValidation
{
    protected $rule = 'image';
}
```
  
> 其他例子，请查看example
## 注意

```php
    /**
     * 需要绝对路径.
     * @var \App\DTO\Address[]
     */
    #[ApiModelProperty('地址')]
    public array $addressArr;
```
- 映射数组类时,`@var`需要写绝对路径
- 控制器中使用了框架`AutoController`注解,只收集了`POST`方法
## Swagger界面
![hMvJnQ](https://gitee.com/tw666/source/raw/master/img/swagger.png)



