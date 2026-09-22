---
name: api-docs
description: tangwei/apidocs 组件（Hyperf Swagger/OpenAPI 文档自动生成）使用指南。内容包括注解编写（#[Api]、#[ApiOperation]、#[ApiResponse]、#[ApiModelProperty]、#[ApiHeader] 等）、DTO 与文档联动、config/autoload/api_docs.php 配置、文档 UI 与 llms.txt 访问、故障排除。当需要为控制器编写/修改 API 注解、生成或排查 Swagger 文档、配置 API 文档组件时使用。
---

# Hyperf API Docs（tangwei/apidocs）使用指南

本组件基于 PHP 8 Attributes 扫描控制器路由，在**应用启动时**自动生成 OpenAPI 3.x 描述文件，并提供 Swagger UI、Knife4j、ReDoc、RapiDoc、Scalar 等多种文档界面及供 AI 读取的 llms.txt 输出。

核心依赖：文档中的 DTO、验证与注解扫描由 `tangwei/dto` 组件（`Hyperf\DTO\*` 命名空间）提供。

## 快速开始

```bash
composer require tangwei/apidocs
php bin/hyperf.php vendor:publish tangwei/apidocs   # 发布配置到 config/autoload/api_docs.php
php bin/hyperf.php start                            # 启动后访问 http://127.0.0.1:9501/swagger
```

最小配置（`config/autoload/api_docs.php`）：

```php
<?php

use function Hyperf\Support\env;

return [
    'enable' => env('APP_ENV') !== 'prod',
    'format' => 'json',                                // json 或 yaml
    'output_dir' => BASE_PATH . '/runtime/container',  // OpenAPI 文件输出目录
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
];
```

零配置即可使用，以上为常用项；完整配置模板见组件 `publish/api_docs.php`。

## 核心注解速查

所有注解位于 `Hyperf\ApiDocs\Annotation\` 命名空间。

### Api —— 控制器分组

```php
use Hyperf\ApiDocs\Annotation\Api;

#[Api(tags: '用户管理', description: '用户相关接口', position: 1)]
class UserController
{
}
```

参数：`tags`（字符串或数组）、`description`、`position`（排序位置）、`hidden`。

### ApiOperation —— 接口描述

```php
use Hyperf\ApiDocs\Annotation\ApiOperation;

#[ApiOperation(summary: '获取用户详情', description: '根据用户ID获取详细信息')]
public function getUser(int $id) { }

#[ApiOperation('登录', security: false)]   // security: false 表示无需认证
public function login() { }
```

参数：`summary`、`description`、`hidden`、`security`（默认 `true`）、`deprecated`。

**注意：位置参数与命名参数不能混用**。`#[ApiOperation('登录', security: false)]` 合法，但 `#[ApiOperation('登录', summary: '登录')]` 会因 `summary` 被重复赋值而报错——第一个位置参数就是 `summary`。

### ApiResponse —— 补充响应定义

控制器方法的**返回类型**会自动生成响应文档；`ApiResponse` 用于补充额外状态码或覆盖返回结构。

```php
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\DTO\Type\PhpType;

#[ApiResponse(Address::class, 201)]              // 指定返回类 + 状态码
#[ApiResponse([PhpType::INT], 204, '简单类型数组')]
#[ApiResponse(new Page([Address::class]), 206)]  // 对象实例示例
// ApiVariable 可变类型可用 types 映射写法，无需实例化：
#[ApiResponse(Page::class, 207, '分页数据', types: ['content' => [Address::class]])]
public function getUser(int $id) { }
```

参数顺序：`returnType`、`response`（默认 `'200'`）、`description`、`types`。

`types`：键为返回类中 `#[ApiVariable]` 标记的属性名，值为类名 / `PhpType` / 实例 / 单元素数组（表示数组）。仅在 `returnType` 为单个类名时生效；嵌套可变类型可传实例，如 `types: ['content' => [new CodeResponse(PhpType::INT)]]`。

### ApiHeader —— 请求头(不常用)

类或方法级别，可重复标注。

```php
use Hyperf\ApiDocs\Annotation\ApiHeader;

#[ApiHeader('apiHeader')]
#[ApiHeader(name: 'Authorization', required: true, description: 'Bearer Token')]
public function getUser(int $id) { }
```

参数：`name`、`required`、`type`（默认 `string`）、`default`、`description`、`format`、`hidden`。

### ApiFormData —— multipart 表单字段

```php
use Hyperf\ApiDocs\Annotation\ApiFormData;

#[ApiFormData(name: 'photo', format: 'binary', required: true)]
public function upload(#[RequestFormData] DemoFormData $formData) { }
```

参数与 `ApiHeader` 相同（`name`、`required`、`type`、`default`、`description`、`format`、`hidden`）。

### ApiModelProperty —— DTO 属性描述

```php
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Type\PhpType;

#[ApiModelProperty('用户名称')]
public string $name;

#[ApiModelProperty(value: '年龄', example: 18, required: true)]
public int $age;

#[ApiModelProperty(value: '年龄', simpleType: PhpType::INT)]  // 显式指定类型
public $age;

#[ApiModelProperty(value: '密码', hidden: true)]               // 文档中隐藏
public string $password;
```

参数：`value`、`example`、`hidden`、`required`、`simpleType`（`PhpType` 枚举：`INT`/`STRING`/`BOOL`/`FLOAT`/`ARRAY`/`OBJECT`）。

### ApiModel —— DTO 类描述

```php
use Hyperf\ApiDocs\Annotation\ApiModel;

#[ApiModel('用户请求')]
class UserRequest { }
```

### ApiSecurity —— 安全方案(不常用,一般在全局配置中使用)

```php
use Hyperf\ApiDocs\Annotation\ApiSecurity;

#[ApiSecurity]                                     // 使用配置中的全局 securitySchemes
#[ApiSecurity(name: 'Authorization', value: [])]
public function getUser(int $id) { }
```

配合 `config/autoload/api_docs.php` 中 `swagger.components.securitySchemes` 与 `swagger.security` 使用。

### ApiVariable —— 运行时可变类型

标记类型需在运行时才能确定的属性（通常用于全局响应包装的 `data` 字段），组件会生成代理类处理。

```php
use Hyperf\ApiDocs\Annotation\ApiVariable;

class GlobalResponse
{
    public string $code = '200';

    #[ApiVariable]
    public mixed $data;

    public string $message = '';
}
```

在 `ApiResponse` 中声明可变类型的具体类型时，优先用 `types` 映射写法（见上文 ApiResponse 章节），无需 new 实例。

## DTO 与文档联动

### 请求参数绑定

请求 DTO 通过 `tangwei/dto` 的参数注解绑定到接口的不同位置：

```php
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\RequestHeader;
use Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Hyperf\DTO\Annotation\Contracts\Valid;

// 请求体（application/json）
public function create(#[RequestBody] #[Valid] UserRequest $request): UserResponse { }

// URL 查询参数
public function list(#[RequestQuery] PageQuery $query): Page { }

// 请求头（DTO 属性映射到 header 名）
public function detail(#[RequestHeader] #[Valid] DemoToken $header): UserResponse { }

// 表单/multipart
public function upload(#[RequestFormData] DemoFormData $formData): array { }
```

- 带 `#[Valid]` 的参数会在请求进入时自动执行属性上的验证注解，失败抛 `ValidationException`（默认 422 响应）。
- 简单类型参数直接写在方法签名上（如 `int $id`），由路由自动绑定路径/查询参数。

### 属性注解

```php
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\ArrayType;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Type\PhpType;

class UserRequest
{
    #[ApiModelProperty('用户ID')]
    #[Required]
    public int $id;

    // 字段别名：文档与输入输出中均为 user_name
    #[ApiModelProperty(value: '用户名', example: '张三')]
    #[JSONField('user_name')]
    #[Required]
    public string $userName;

    // 数组元素类型：声明文档 schema 中 items 的类型
    #[ApiModelProperty('标签列表')]
    #[ArrayType('string')]     // 也可 #[ArrayType(User::class)] 或 #[ArrayType(PhpType::INT)]
    public array $tags;

    /**
     * @var User[]   // PHPDoc 写法同样被识别
     */
    #[ApiModelProperty('用户列表')]
    public array $users;
}
```

### DTO 验证常用注解

验证注解位于 `Hyperf\DTO\Annotation\Validation\*`，均为**属性注解、可叠加**，构造参数 `messages:` 可自定义错误消息。

| 注解 | 说明 | 示例 |
|------|------|------|
| `#[Required]` | 必填 | `#[Required]` |
| `#[Nullable]` | 允许 null | `#[Nullable]` |
| `#[Integer]` / `#[Numeric]` | 整数 / 数值 | `#[Integer]` |
| `#[Boolean]` | 布尔值 | `#[Boolean(strict: true)]` |
| `#[Str]` | 字符串 | `#[Str]` |
| `#[Email]` / `#[Url]` / `#[Date]` / `#[Json]` / `#[Image]` | 格式校验 | `#[Email(messages: '邮箱格式不正确')]` |
| `#[Between]` / `#[Max]` / `#[Min]` / `#[Size]` | 范围 / 大小 | `#[Between(min: 2, max: 10)]`、`#[Max(100)]` |
| `#[In]` / `#[NotIn]` | 值在 / 不在列表中 | `#[In(['a', 'b'])]` |
| `#[Regex]` / `#[NotRegex]` / `#[StartsWith]` | 模式匹配 | `#[Regex('/^\d+$/')]` |
| `#[Alpha]` / `#[AlphaNum]` / `#[AlphaDash]` | 字符集 | `#[AlphaNum]` |
| `#[Arr]` | 数组 | `#[Arr]` |
| `#[Distinct]` | 数组元素不重复 | `#[Distinct]` |
| `#[Present]` / `#[Bail]` / `#[Declined]` / `#[Accepted]` 等 | 其他 Laravel 风格规则 | 见源码 |

通用注解 `#[Validation]` 支持原生规则串（多条用 `|` 分隔）及 `customKey` 指定数组元素规则：

```php
#[Validation('required|numeric')]
#[Validation('required|numeric', '必填|必须为数字')]   // 消息与规则一一对应
#[Validation('integer', customKey: 'tags.*')]         // 校验数组每个元素
public array $tags;
```

**验证注解与文档的关系**：属性上的 `#[Required]` 会写入 schema 的 required 列表，`#[In]` 会生成 enum 约束；其余验证注解主要在**运行时请求验证**（配合参数 `#[Valid]`）中生效，不会写入文档 schema——需要出现在文档里的约束请用 `#[ApiModelProperty]`（`value`/`example`/`required`/`simpleType`）或 `#[ApiResponse]` 表达。

### DTO 与实体互转（Mapper / from）

`Hyperf\DTO\Mapper` 提供对象拷贝与映射，常见做法是在响应 DTO 上定义静态 `from` 方法完成 实体 → DTO 转换，控制器直接返回该 DTO（schema 自动生成）：

```php
use Hyperf\DTO\Mapper;

class ActivityResponse
{
    #[ApiModelProperty('活动名称')]
    #[JSONField('activity_name')]
    public string $activityName;

    /**
     * @var ActivityUser[]
     */
    #[ApiModelProperty('参与用户')]
    public array $activityUser;

    public static function from(?Activity $obj): ?self
    {
        return Mapper::copyProperties($obj, new self());
    }
}
```

控制器中使用：

```php
#[ApiOperation(summary: '活动详情')]
#[GetMapping(path: 'activity/{id}')]
public function activity(int $id): ActivityResponse
{
    $activity = Activity::query()->find($id);
    return ActivityResponse::from($activity);
}
```

Mapper 常用方法：

| 方法 | 说明 |
|------|------|
| `Mapper::copyProperties($source, $target)` | 把对象（或 `Arrayable`）属性拷贝到 `$target` 实例；`$source` 为 `null` 时返回 `null` |
| `Mapper::map($json, $object)` | 数组/JSON 数据映射到对象实例 |
| `Mapper::mapArray($json, $className)` | 数组/JSON 映射为指定类的对象数组 |

### `#[Dto]` 注解与响应字段名转换(不常用)

类级注解 `#[Dto]` 可指定响应输出时的字段名转换策略：

```php
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Type\Convert;

#[Dto(responseConvert: Convert::SNAKE)]   // 输出时 userName → user_name
class UserResponse { }
```

`Convert` 可选值：`CAMEL`、`STUDLY`、`SNAKE`、`NONE`、`CUSTOM`（`CUSTOM` 通过 `ConvertCustom` 注册闭包）。`#[JSONField]` 别名优先级高于转换策略。

## 完整示例

```php
<?php

namespace App\Controller;

use App\Dto\Request\PageQuery;
use App\Dto\Request\UserRequest;
use App\Dto\Response\UserResponse;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller(prefix: '/user')]
#[Api(tags: '用户管理', position: 1)]
#[ApiHeader('apiHeader')]
class UserController
{
    #[ApiOperation(summary: '查询用户详情')]
    #[GetMapping(path: 'get/{id}')]
    #[ApiResponse(UserResponse::class, 200, '成功')]
    #[ApiResponse(null, 404, '用户不存在')]
    public function getUser(int $id): UserResponse
    {
    }

    #[ApiOperation(summary: '创建用户', description: '返回创建后的用户信息')]
    #[PostMapping(path: 'create')]
    #[ApiResponse(UserResponse::class, 201, '创建成功')]
    public function create(#[RequestBody] #[Valid] UserRequest $request): UserResponse
    {
    }

    #[ApiOperation(summary: '分页查询')]
    #[GetMapping(path: 'page')]
    public function page(#[RequestQuery] PageQuery $query): Page
    {
    }
}
```

真实用法可参考组件 `example/` 目录（活文档，覆盖数组、递归、枚举、分页等复杂类型）。

**要点**：控制器方法尽量返回具体类（而非 `array`/`object`），这样能自动生成准确的响应 schema；返回结构无法用类表达时用 `#[ApiResponse]` 补充。

## 全局响应包装

当项目通过 AOP 统一包装返回格式（如 `{code, data, message}`）时，配置代理类并配合 `#[ApiVariable]`：

```php
// config/autoload/api_docs.php
'global_return_responses_class' => \Hyperf\ApiDocs\DTO\GlobalResponse::class,
```

`GlobalResponse` 的 `data` 属性用 `#[ApiVariable]` 标记，实际返回哪个类的结构由接口方法返回类型决定，组件运行时生成代理类合并文档。类结构参考组件 `src/DTO/GlobalResponse.php`。

## 文档访问入口

| 路径 | 说明 |
|------|------|
| `{prefix_url}`（默认 `/swagger`） | Swagger UI |
| `/swagger/doc` | Knife4j UI（需安装 `tangwei/knife4j-ui`） |
| `/swagger/redoc` | ReDoc |
| `/swagger/rapidoc` | RapiDoc |
| `/swagger/scalar` | Scalar |
| `/swagger/{server}.json` / `.yaml` | OpenAPI 描述文件 |
| `/swagger/llms.txt` | 所有接口的 Markdown 链接索引（供 AI 读取） |
| `/swagger/{server}.md` | 某 server 的全部接口 Markdown 列表 |
| `/swagger/{server}/{operationId}.md` | 单个接口详情（含 OpenAPI YAML 片段） |

`{server}` 为服务名（默认 `http`）。llms.txt 系列端点便于 AI 直接读取在线接口文档；本文档（SKILL.md）则是编码时离线参考。

## 配置参考

`config/autoload/api_docs.php` 常用键：

| 键 | 说明 |
|----|------|
| `enable` | 是否启用（生产环境建议关闭） |
| `format` | 输出格式 `json` / `yaml` |
| `output_dir` | OpenAPI 文件输出目录（默认 `runtime/container`） |
| `proxy_dir` | 代理类生成目录 |
| `prefix_url` | 文档路由前缀，默认 `/swagger` |
| `prefix_swagger_resources` | Swagger UI 静态资源 CDN 地址 |
| `global_return_responses_class` | 全局响应包装类 |
| `validation_custom_attributes` | 用 `ApiModelProperty` 的值作为验证提示信息 |
| `dto_default_value_level` | DTO 默认值等级：0 不设置；1 简单类型设默认值；2 复杂类型也设 null（慎用） |
| `responses` | 全局响应（如 401/500），映射为 `ApiResponse`（支持 `returnType`/`types` 键） |
| `swagger.info` | 文档标题、版本、描述 |
| `swagger.servers` | 服务地址列表 |
| `swagger.components.securitySchemes` | 安全方案定义 |
| `swagger.security` | 全局安全要求 |

**配置键名必须与 `SwaggerConfig` 属性名一致（snake_case）**，自定义时注意对应。

## 故障排除

- **文档未生成**：确认 `enable` 非 `false`；`output_dir` 存在且可写；查看启动日志中 Swagger 相关输出。
- **注解修改不生效**：OpenAPI 文件在**启动时**生成，修改注解后需**重启服务**；如启用了扫描缓存（tangwei/dto 的缓存配置），需清除 `runtime/container` 后重启。
- **多 server 状态**：每个 HTTP server 各生成一份 `{server}.json`，检查访问的是否为对应服务文件。
- **类名冲突**：调用 `Hyperf\ApiDocs\Swagger\SwaggerCommon::simpleClassNameClear()` 清理缓存。
- **Knife4j UI 500**：`/swagger/doc` 依赖 `tangwei/knife4j-ui`，未安装时请使用默认 Swagger UI。

## 最佳实践

1. 控制器方法返回具体类，让响应 schema 自动生成
2. 请求参数用 DTO + `#[Valid]`，验证注解会自动映射到文档约束
3. 用 `tags` + `position` 对接口分模块组织
4. 为字段提供 `example`，提升文档可读性
5. 弃用接口标记 `deprecated: true`，勿直接删除
