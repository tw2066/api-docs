# PHP Swagger Api Docs
[![Latest Stable Version](https://img.shields.io/packagist/v/tangwei/apidocs)](https://packagist.org/packages/tangwei/apidocs)
[![Total Downloads](https://img.shields.io/packagist/dt/tangwei/apidocs)](https://packagist.org/packages/tangwei/apidocs)
[![License](https://img.shields.io/packagist/l/tangwei/apidocs)](https://github.com/tw2066/api-docs)

基于 [Hyperf](https://github.com/hyperf/hyperf) 框架的 swagger 文档生成组件，支持swoole/swow驱动

## 优点

- 声明参数类型完成自动注入，参数映射到PHP类，根据类和注解自动生成Swagger文档
- 代码DTO模式，可维护性好，扩展性好
- 支持数组(类/简单类型)，递归，嵌套
- 支持注解数据校验
- 支持api token
- 支持PHP8原生注解，PHP8.1枚举
- 支持openapi 3.0

## 使用须知

* php版本 >= 8.1，参数映射到PHP类不支持联合类型
* 控制器中方法尽可能返回类(包含简单类型)，这样会更好的生成文档
* 当返回类的结果满足不了时,可以使用 #[ApiResponse] 注解

## 例子

> 请参考[example目录](https://github.com/tw2066/api-docs/tree/master/example)

## 安装

```
composer require tangwei/apidocs
```
默认使用swagger-ui,可使用knife4j-ui(功能更强大)

```
composer require tangwei/knife4j-ui
```

## 使用

### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish tangwei/apidocs
```

#### 1.1 配置信息

> config/autoload/api_docs.php

```php
<?php

declare(strict_types=1);

use Hyperf\ApiDocs\DTO\GlobalResponse;
use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | 启动 swagger 服务
    |--------------------------------------------------------------------------
    |
    | false 将不会启动 swagger 服务
    |
    */
    'enable' => env('APP_ENV') !== 'prod',

    /*
    |--------------------------------------------------------------------------
    | 生成swagger文件格式
    |--------------------------------------------------------------------------
    |
    | 支持json和yaml
    |
    */
    'format' => 'json',

    /*
    |--------------------------------------------------------------------------
    | 生成swagger文件路径
    |--------------------------------------------------------------------------
    */
    'output_dir' => BASE_PATH . '/runtime/container',

    /*
    |--------------------------------------------------------------------------
    | 生成代理类路径
    |--------------------------------------------------------------------------
    */
    'proxy_dir' => BASE_PATH . '/runtime/container/proxy',

    /*
    |--------------------------------------------------------------------------
    | 设置路由前缀
    |--------------------------------------------------------------------------
    */
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),

    /*
    |--------------------------------------------------------------------------
    | 设置swagger资源路径,cdn资源
    |--------------------------------------------------------------------------
    */
    'prefix_swagger_resources' => 'https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.5.0',

    /*
    |--------------------------------------------------------------------------
    | 设置全局返回的代理类
    |--------------------------------------------------------------------------
    |
    | 全局返回 如:[code=>200,data=>null] 格式,设置会后会全局生成对应文档
    | 配合ApiVariable注解使用,示例参考GlobalResponse类
    | 返回数据格式可以利用AOP统一返回
    |
    */
    // 'global_return_responses_class' => GlobalResponse::class,

    /*
    |--------------------------------------------------------------------------
    | 替换验证属性
    |--------------------------------------------------------------------------
    |
    | 通过获取注解ApiModelProperty的值,来提供数据验证的提示信息
    |
    */
    'validation_custom_attributes' => true,

    /*
    |--------------------------------------------------------------------------
    | 设置DTO类默认值等级
    |--------------------------------------------------------------------------
    |
    | 设置:0 默认(不设置默认值)
    | 设置:1 简单类型会为设置默认值,复杂类型(带?)会设置null
    |        - 简单类型默认值: int:0  float:0  string:''  bool:false  array:[]  mixed:null
    | 设置:2 (慎用)包含等级1且复杂类型(联合类型除外)会设置null
    |
    */
    'dto_default_value_level' => 0,

    /*
    |--------------------------------------------------------------------------
    | 全局responses,映射到ApiResponse注解对象
    |--------------------------------------------------------------------------
    */
    'responses' => [
        ['response' => 401, 'description' => 'Unauthorized'],
        ['response' => 500, 'description' => 'System error'],
    ],
    /*
    |--------------------------------------------------------------------------
    | swagger 的基础配置
    |--------------------------------------------------------------------------
    |
    | 该属性会映射到OpenAPI对象
    |
    */
    'swagger' => [
        'info' => [
            'title' => 'API DOC',
            'version' => '0.1',
            'description' => 'swagger api desc',
        ],
        'servers' => [
            [
                'url' => 'http://127.0.0.1:9501',
                'description' => 'OpenApi host',
            ],
        ],
        'components' => [
            'securitySchemes' => [
                [
                    'securityScheme' => 'Authorization',
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'Authorization',
                ],
            ],
        ],
        'security' => [
            ['Authorization' => []],
        ],
        'externalDocs' => [
            'description' => 'Find out more about Swagger',
            'url' => 'https://github.com/tw2066/api-docs',
        ],
    ],
];
```

### 2. 直接启动框架(需要有http服务)

```shell script
php bin/hyperf.php start

[INFO] Swagger docs url at http://0.0.0.0:9501/swagger
[INFO] TaskWorker#1 started.
[INFO] Worker#0 started.
[INFO] HTTP Server listening at 0.0.0.0:9501
```

> 看到`Swagger docs url`显示，表示文档生成成功，访问`/swagger`即可以看到swagger页面
> 安装[knife4j-ui](https://github.com/tw2066/knife4j-ui)，访问`/swagger/doc`即可以看到knife4j页面
> 访问`/swagger/redoc`，可以看到[redoc](https://github.com/Redocly/redoc)页面

### 3. 使用

## 注解

> 命名空间:`Hyperf\DTO\Annotation\Contracts`

#### #[RequestBody] 注解

- 获取Body参数

```php
public function add(#[RequestBody] DemoBodyRequest $request){}
```

#### #[RequestQuery] 注解

- 获取GET参数

```php
public function add(#[RequestQuery] DemoQuery $request){}
```

#### #[RequestFormData] 注解

- 获取表单请求

```php
public function fromData(#[RequestFormData] DemoFormData $formData){}
```

- 获取文件(和表单一起使用)

```php
#[ApiFormData(name: 'photo', format: 'binary')]
```

- 获取Body参数和GET参数

```php
public function add(#[RequestBody] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query){}
```

#### #[ApiSecurity] 注解

- 优先级: 方法 > 类 > 全局

```php
#[ApiSecurity('Authorization')]
public function getUserInfo(DemoToken $header){}
```

> 注意: 一个方法，不能同时注入RequestBody和RequestFormData

#### #[ApiResponse] 注解

* php暂不能定义数组类型，返回的数据类型不能完全满足

  当不能满足时，可以通过ApiResponse注解来解决

  ```php
  use Hyperf\ApiDocs\Annotation\ApiResponse; 
  use Hyperf\DTO\Type\PhpType;
  
  #[ApiResponse([PhpType::BOOL], 201)]
  #[ApiResponse([PhpType::INT], 202)]
  #[ApiResponse([PhpType::FLOAT], 203)]
  #[ApiResponse([PhpType::ARRAY], 204)]
  #[ApiResponse([PhpType::OBJECT], 205)]
  #[ApiResponse([PhpType::STRING], 206)]
  #[ApiResponse([Address::class], 207)]
  #[ApiResponse([PhpType::INT], 208)]
  #[ApiResponse([PhpType::BOOL])]
  public function test(){}
  ```

* php暂不支持泛型，当返回存在相同结构时候，需要写很多类来返回

  例: 分页只有`content`结构是可变，可以通过`#[ApiVariable]`配合使用

  ```php
  use Hyperf\ApiDocs\Annotation\ApiVariable;
  
  class Page
  {
      public int $total;
  
      #[ApiVariable]
      public array $content;
  
      public function __construct(array $content, int $total = 0)
      {
          $this->content = $content;
          $this->total = $total;
      }
  }
  ```

  控制器

  ```php
      #[ApiOperation('分页')]
      #[GetMapping(path: 'activityPage')]
      #[ApiResponse(new Page([ActivityResponse::class]))]
      public function activityPage(#[RequestQuery] PageQuery $pageQuery): Page
      {
          $activityPage = Activity::paginate($pageQuery->getSize());
          $arr = [];
          foreach ($activityPage as $activity) {
              $arr[] = ActivityResponse::from($activity);
          }
          return new Page($arr, $activityPage->total());
      }
  ```

  通过`#[ApiResponse(new Page([ActivityResponse::class]))]`会生成相应的文档



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

### 自定义注解验证

> 注解的验证支持框架所有验证, 组件提供了常用的注解用于验证

1. 使用自定义验证注解, 创建注解类继承`Hyperf\DTO\Annotation\Validation\BaseValidation`
2. 重写`$rule`属性或`getRule`方法

```php
//示例
#[Attribute(Attribute::TARGET_PROPERTY)]
class Image extends BaseValidation
{
    protected $rule = 'image';
}
```

### 验证器Validation

1. 大家都习惯了框架的`required|date|after:start_date`写法

```php
//可以通过Validation实现
#[Validation('required|date|after:start_date')]
```

2. 需要支持数组里面是int数据情况 `'intArr.*' => 'integer'`的情况

```php
//可以通过Validation中customKey来自定义key实现
#[Validation('integer', customKey: 'intArr.*')]
public array $intArr;
```

## 注意

### 数组类型的问题

> PHP原生暂不支持`int[]`或`Class[]`类型, 使用示例

```php
    /**
     * class类型映射数组.
     * @var \App\DTO\Address[]
     */
    #[ApiModelProperty('地址')]
    public array $addressArr;

    /**
     * 简单类型映射数组.
     * @var int[]
     */
    #[ApiModelProperty('int类型的数组')]
    public array $intArr;

    /**
     * 通过注解映射数组.
     */
    #[ApiModelProperty('string类型的数组')]
    #[ArrayType('string')]
    public array $stringArr;
```

### `AutoController`注解

> 控制器中使用`AutoController`注解,只收集了`POST`方法

## DTO数据映射

> api-docs引入到dto组件

### 注解

#### Dto注解

标记为dto类

```php
use Hyperf\DTO\Annotation\Dto;

#[Dto]
class DemoQuery
{
}
```

* 可以设置返回枚举`#[Dto(Convert::SNAKE)]`, 批量转换下划线返回的key
* `Dto`注解不会生成文档, 要生成对应文档使用`JSONField`注解

#### JSONField注解

用于设置属性的别名

```php
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;

#[Dto]
class DemoQuery
{
    #[ApiModelProperty('这是一个别名')]
    #[JSONField('alias_name')]
    #[Required]
    public string $name;   
}
```

* 设置JSONField后会生成代理类,生成`alias_name`属性
* 接受和返回字段都以`alias_name` 为准

## RPC [返回PHP对象](https://hyperf.wiki/3.1/#/zh-cn/json-rpc?id=%e8%bf%94%e5%9b%9e-php-%e5%af%b9%e8%b1%a1)
> aspects.php中配置
```php
return [
    \Hyperf\DTO\Aspect\ObjectNormalizerAspect::class
]
```
> 当框架导入 symfony/serializer (^5.0) 和 symfony/property-access (^5.0) 后，并在 dependencies.php 中配置一下映射关系
```php
use Hyperf\Serializer\SerializerFactory;
use Hyperf\Serializer\Serializer;

return [
    Hyperf\Contract\NormalizerInterface::class => new SerializerFactory(Serializer::class),
];
```

## Phar 打包器

```shell
# 1.启动生成代理类和注解缓存
php bin/hyperf.php start
# 2.打包
php bin/hyperf.php phar:build
```

## Swagger界面

![hMvJnQ](https://gitee.com/tw666/source/raw/master/img/swagger.png)

## PHP Accessor

生成类访问器（Getter & Setter）

推荐使用[free2one/hyperf-php-accessor](https://github.com/kkguan/hyperf-php-accessor)

