# PHP Hyperf API Docs

[![Latest Stable Version](https://img.shields.io/packagist/v/tangwei/apidocs)](https://packagist.org/packages/tangwei/apidocs)
[![Total Downloads](https://img.shields.io/packagist/dt/tangwei/apidocs)](https://packagist.org/packages/tangwei/apidocs)
[![License](https://img.shields.io/packagist/l/tangwei/apidocs)](https://github.com/tw2066/api-docs)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://www.php.net)

[English](./README_EN.md) | 中文

基于 [Hyperf](https://github.com/hyperf/hyperf) 框架的 Swagger/OpenAPI 文档自动生成组件，支持 Swoole/Swow 引擎，为您提供优雅的 API 文档解决方案。

## ✨ 特性

- 🚀 **自动生成** - 基于 PHP 8 Attributes 自动生成 OpenAPI 3.0/3.1 文档
- 🎯 **类型安全** - 支持 DTO 模式，参数自动映射到 PHP 类
- 📝 **多种 UI** - 支持 Swagger UI、Knife4j、Redoc、RapiDoc、Scalar 等多种文档界面
- ✅ **数据验证** - 集成 Hyperf 验证器，支持丰富的验证注解
- 🔒 **安全认证** - 支持 API Token 和多种安全方案
- 🔄 **类型支持** - 支持数组、递归、嵌套、枚举等复杂类型
- 🎨 **灵活配置** - 可自定义全局响应格式、路由前缀等
- 📦 **开箱即用** - 零配置即可使用，同时支持深度定制

## 📋 环境要求

- PHP >= 8.1
- Hyperf >= 3.0
- Swoole >= 5.0 或 Swow

## 💡 使用须知

- 控制器方法尽可能返回具体的类（包含简单类型），这样能更好地生成文档
- 当返回类无法满足需求时，可使用 `#[ApiResponse]` 注解补充

## 📦 安装

```bash
composer require tangwei/apidocs
```

默认使用 Swagger UI，推荐安装 Knife4j UI（可选）：

```bash
composer require tangwei/knife4j-ui
```

## 🚀 快速开始

### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish tangwei/apidocs
```

配置文件将发布到 `config/autoload/api_docs.php`

### 2. 基础配置

```php
<?php
// config/autoload/api_docs.php
return [
    // 启用文档服务（建议生产环境禁用）
    'enable' => env('APP_ENV') !== 'prod',
    
    // 文档访问路径
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
    
    // 基础信息
    'swagger' => [
        'info' => [
            'title' => 'API 文档',
            'version' => '1.0.0',
            'description' => '项目 API 文档',
        ],
        'servers' => [
            [
                'url' => 'http://127.0.0.1:9501',
                'description' => 'API 服务器',
            ],
        ],
    ],
];
```

> 完整配置文件示例：config/autoload/api_docs.php

<details>
  <summary>完整配置说明（点击展开）</summary>
  <p>

```php
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
 </p>
</details>

### 3. 启动服务

```bash
php bin/hyperf.php start
```
```
[INFO] Swagger docs url at http://0.0.0.0:9501/swagger
[INFO] Worker#0 started.
[INFO] HTTP Server listening at 0.0.0.0:9501
```

- 启动成功后，访问 `http://your-host:9501/swagger` 即可查看 API 文档。
- 访问 `http://your-host:9501/swagger/llms.txt` 包含控制器每个Markdown页面的链接,可以用于Ai快速访问编程文档。
- 其他服务访问 `http://your-host:9501/swagger/{service-name}.md` 访问 `{service-name}` 服务的 Markdown 文档。

## 📖 使用指南

### 基础示例

#### 1. 定义 DTO 类

```php
<?php

namespace App\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Between;

class UserRequest
{
    #[ApiModelProperty('用户名')]
    #[Required]
    public string $username;

    #[ApiModelProperty('年龄')]
    #[Required]
    #[Integer]
    #[Between(1, 120)]
    public int $age;

    #[ApiModelProperty('邮箱')]
    public ?string $email = null;
}
```

#### 2. 编写控制器

```php
<?php

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use App\Request\UserRequest;

#[Controller(prefix: '/user')]
#[Api(tags: '用户管理', position: 1)]
class UserController
{
    #[GetMapping(path: 'list')]
    #[ApiOperation(summary: '获取用户列表')]
    public function list(#[RequestQuery] #[Valid] UserRequest $request): array
    {
        return [
            ['id' => 1, 'username' => 'admin'],
            ['id' => 2, 'username' => 'user'],
        ];
    }

    #[PostMapping(path: 'create')]
    #[ApiOperation(summary: '创建用户')]
    public function create(#[RequestBody] #[Valid] UserRequest $request): array
    {
        return [
            'id' => 1,
            'username' => $request->username,
            'age' => $request->age,
        ];
    }
}
```

## 🎨 注解参考

### 控制器注解

#### `#[Api]` - 控制器标签

```php
#[Api(
    tags: '用户管理',         // 标签名称（支持数组）
    description: '用户相关操作', // 描述
    position: 1,             // 排序位置
    hidden: false           // 是否隐藏
)]
```

#### `#[ApiOperation]` - API 操作

```php
#[ApiOperation(
    summary: '创建用户',       // 摘要
    description: '详细描述',   // 详细描述
    deprecated: false,        // 是否废弃
    security: true,          // 是否需要认证
    hidden: false           // 是否隐藏
)]
```

#### `#[ApiResponse]` - 响应定义

```php
// 简单类型响应
#[ApiResponse(PhpType::INT, 200, '成功')]

// 对象响应
#[ApiResponse(UserResponse::class, 200, '用户信息')]

// 数组响应
#[ApiResponse([UserResponse::class], 200, '用户列表')]

// 分页响应
#[ApiResponse(new Page([UserResponse::class]), 200, '分页数据')]
```

**泛型支持示例：**

PHP 暂不支持泛型，可通过 `#[ApiVariable]` 实现：

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

控制器使用：

```php
#[ApiOperation('分页查询')]
#[GetMapping(path: 'page')]
#[ApiResponse(new Page([UserResponse::class]))]
public function page(#[RequestQuery] PageQuery $query): Page
{
    // 返回分页数据
}
```

### 参数注解

#### `#[RequestBody]` - Body 参数

获取 POST/PUT/PATCH 请求的 JSON body 参数：

```php
public function create(#[RequestBody] #[Valid] UserRequest $request)
{
    // $request 自动填充 body 数据
}
```

#### `#[RequestQuery]` - Query 参数

获取 URL 查询参数（GET 参数）：

```php
public function list(#[RequestQuery] #[Valid] QueryRequest $request)
{
    // $request 自动填充查询参数
}
```

#### `#[RequestFormData]` - 表单参数

获取表单数据（multipart/form-data）：

```php
#[ApiFormData(name: 'photo', format: 'binary')]
public function upload(#[RequestFormData] UploadRequest $formData)
{
    $file = $this->request->file('photo');
    // 处理文件上传
}
```

#### `#[RequestHeader]` - 请求头参数

获取请求头信息：

```php
public function auth(#[RequestHeader] #[Valid] AuthHeader $header)
{
    // $header 自动填充请求头数据
}
```

> ⚠️ **注意**：一个方法不能同时注入 `RequestBody` 和 `RequestFormData`

### 属性注解

#### `#[ApiModelProperty]` - 属性描述

```php
#[ApiModelProperty(
    value: '用户名',        // 属性描述
    example: 'admin',      // 示例值
    required: true,        // 是否必填
    hidden: false         // 是否隐藏
)]
public string $username;
```

#### `#[ApiHeader]` - 请求头定义

```php
// 全局请求头（类级别）
#[ApiHeader('X-Request-Id')]

// 方法级请求头
#[ApiHeader(
    name: 'Authorization',
    required: true,
    type: 'string',
    description: 'Bearer token'
)]
```

#### `#[ApiSecurity]` - 安全认证

优先级：方法 > 类 > 全局

```php
// 使用默认认证
#[ApiSecurity('Authorization')]

// 方法级覆盖
#[ApiOperation(summary: '登录', security: false)]  // 不需要认证
```



## ✅ 数据验证

### 内置验证注解

组件提供丰富的验证注解支持：

```php
use Hyperf\DTO\Annotation\Validation\*;

class UserRequest
{
    #[Required]                        // 必填
    #[Max(50)]                         // 最大长度
    public string $username;

    #[Required]
    #[Integer]                         // 整数
    #[Between(1, 120)]                 // 范围
    public int $age;

    #[Email]                           // 邮箱格式
    public ?string $email;

    #[Url]                             // URL 格式
    public ?string $website;

    #[Regex('/^1[3-9]\d{9}$/')]       // 正则验证
    public ?string $mobile;

    #[In(['male', 'female'])]          // 枚举值
    public ?string $gender;

    #[Date]                            // 日期格式
    public ?string $birthday;
}
```

> 💡 **提示**：只需在控制器方法参数中添加 `#[Valid]` 注解即可启用验证

```php
public function create(#[RequestBody] #[Valid] UserRequest $request)
{
    // 验证自动执行
}
```

### 自定义验证

#### 使用 Validation 注解

```php
// 支持 Laravel 风格的验证规则
#[Validation('required|string|min:3|max:50')]
public string $username;

// 数组元素验证
#[Validation('integer', customKey: 'ids.*')]
public array $ids;
```

#### 自定义验证注解

```php
<?php

namespace App\Validation;

use Attribute;
use Hyperf\DTO\Annotation\Validation\BaseValidation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Mobile extends BaseValidation
{
    protected $rule = 'regex:/^1[3-9]\d{9}$/';
    
    public function __construct(string $messages = '手机号格式错误')
    {
        parent::__construct($messages);
    }
}
```

使用自定义验证：

```php
use App\Validation\Mobile;

class RegisterRequest
{
    #[Required]
    #[Mobile]
    public string $phone;
}
```

## 🔧 高级特性

### 数组类型支持

#### 方法一：使用 PHPDoc

```php
/**
 * @var Address[]
 */
#[ApiModelProperty('地址列表')]
public array $addresses;

/**
 * @var int[]
 */
#[ApiModelProperty('ID 列表')]
public array $ids;
```

#### 方法二：使用 ArrayType 注解

```php
use Hyperf\DTO\Annotation\ArrayType;

#[ApiModelProperty('地址列表')]
#[ArrayType(Address::class)]
public array $addresses;

#[ApiModelProperty('标签列表')]
#[ArrayType('string')]
public array $tags;
```

### 嵌套对象

```php
class UserRequest
{
    public string $name;
    
    // 嵌套对象
    #[ApiModelProperty('地址信息')]
    public Address $address;
    
    /**
     * @var Address[]
     */
    #[ApiModelProperty('多个地址')]
    public array $addresses;
}

class Address
{
    public string $province;
    public string $city;
    public string $street;
}
```

### 枚举支持

```php
use Hyperf\DTO\Type\PhpType;

enum StatusEnum: int
{
    case PENDING = 0;
    case ACTIVE = 1;
    case INACTIVE = 2;
}

class OrderRequest
{
    #[ApiModelProperty('订单状态')]
    public StatusEnum $status;
}
```

### 全局响应格式

配置全局响应包装类：

```php
// config/autoload/api_docs.php
return [
    'global_return_responses_class' => \App\DTO\GlobalResponse::class,
];
```

定义全局响应类：

```php
<?php

namespace App\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiVariable;

class GlobalResponse
{
    #[ApiModelProperty('状态码')]
    public int $code = 0;

    #[ApiModelProperty('消息')]
    public string $message = 'success';

    #[ApiVariable]
    #[ApiModelProperty('响应数据')]
    public mixed $data = null;
}
```

### 文件上传

```php
#[PostMapping(path: 'upload')]
#[ApiOperation(summary: '文件上传')]
#[ApiFormData(name: 'file', format: 'binary', required: true)]
#[ApiFormData(name: 'description', type: 'string')]
public function upload(#[RequestFormData] UploadRequest $request)
{
    $file = $this->request->file('file');
    // 处理文件上传
    return ['url' => '/uploads/file.jpg'];
}
```

## 🎭 多种 UI 界面

访问不同的 UI 界面：

- **Swagger UI**: `http://your-host:9501/swagger`
- **Knife4j**: `http://your-host:9501/swagger/knife4j`
- **Redoc**: `http://your-host:9501/swagger/redoc`
- **RapiDoc**: `http://your-host:9501/swagger/rapidoc`
- **Scalar**: `http://your-host:9501/swagger/scalar`

## ⚙️ 配置参考

### DTO 数据映射

> api-docs 依赖 DTO 组件，更多详情请查看 [DTO 文档](https://github.com/hyperf/dto)

#### `#[Dto]` 注解

标记为 DTO 类：

```php
use Hyperf\DTO\Annotation\Dto;

#[Dto]
class DemoQuery
{
}
```

- 可以设置返回格式 `#[Dto(Convert::SNAKE)]`，批量转换为下划线格式的 key
- `Dto` 注解不会生成文档，要生成对应文档使用 `JSONField` 注解

#### `#[JSONField]` 注解

用于设置属性的别名：

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

- 设置 `JSONField` 后会生成代理类，生成 `alias_name` 属性
- 接收和返回字段都以 `alias_name` 为准

### RPC 支持

[返回 PHP 对象](https://hyperf.wiki/3.1/#/zh-cn/json-rpc?id=%e8%bf%94%e5%9b%9e-php-%e5%af%b9%e8%b1%a1)

aspects.php 中配置：

```php
return [
    \Hyperf\DTO\Aspect\ObjectNormalizerAspect::class
]
```

当框架导入 `symfony/serializer (^5.0)` 和 `symfony/property-access (^5.0)` 后，在 dependencies.php 中配置映射关系：

```php
use Hyperf\Serializer\SerializerFactory;
use Hyperf\Serializer\Serializer;

return [
    Hyperf\Contract\NormalizerInterface::class => new SerializerFactory(Serializer::class),
];
```

## 💡 最佳实践

### 1. DTO 类设计

- 使用有意义的类名，如 `CreateUserRequest`、`UserResponse`
- 为每个属性添加 `ApiModelProperty` 注解
- 分离 Request 和 Response 定义
- 合理使用验证注解

### 2. 控制器设计

- 使用 `Api` 注解对控制器分组
- 为每个方法添加 `ApiOperation` 描述
- 尽可能返回具体类型而非 `array`
- 合理使用 `ApiResponse` 定义响应格式

### 3. 安全性

- 生产环境禁用文档服务
- 使用 `ApiSecurity` 控制 API 认证
- 使用 `hidden: true` 隐藏敏感接口

### 4. 性能优化

- 开发环境使用文档，生产环境禁用
- 合理使用缓存
- 避免过深的嵌套结构

## 📚 常见问题

### Q: 文档没有生成？

A: 检查以下几点：
1. 配置文件中 `enable` 是否为 `true`
2. 查看日志是否有错误信息

### Q: 如何定义数组类型？

A: 使用 PHPDoc 注释或 `ArrayType` 注解：

```php
/**
 * @var User[]
 */
public array $users;

// 或
#[ArrayType(User::class)]
public array $users;
```

### Q: 如何隐藏某些接口？

A: 使用 `hidden` 参数：

```php
#[Api(hidden: true)]  // 隐藏整个控制器

#[ApiOperation(summary: '测试', hidden: true)]  // 隐藏单个接口
```

### Q: 如何自定义响应格式？

A: 使用 `ApiResponse` 注解或配置全局响应类：

```php
#[ApiResponse(UserResponse::class, 200, '成功')]
public function getUser(): UserResponse
{
    return new UserResponse();
}
```

### Q: 支持哪些验证规则？

A: 支持所有 Hyperf Validation 规则。详见 [Hyperf 验证器文档](https://hyperf.wiki/3.1/#/zh-cn/validation)。

### Q: `AutoController` 注解支持吗？

A: 支持，但只会收集 `POST` 方法。建议使用标准路由注解以获得更好的文档生成效果。

## 📖 示例项目

> 完整示例请参考 [example 目录](https://github.com/tw2066/api-docs/tree/master/example)

## 🔗 相关链接

- [Hyperf 官方文档](https://hyperf.wiki)
- [OpenAPI 规范](https://swagger.io/specification/)
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Knife4j](https://doc.xiaominfo.com/)
- [示例项目](https://github.com/tw2066/api-docs/tree/master/example)

---

如果这个项目对你有帮助，请给个 ⭐ Star！


