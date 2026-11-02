# 示例目录

本目录是 `tangwei/apidocs` 的注解用法活文档，不参与 Composer autoload。复制到业务项目后，将 `HyperfExample\ApiDocs` 替换为应用命名空间。

## 内容

- `Controller/`：路由、query/body/数组/表单/Header、多状态码响应、分页、具体类响应与只读响应。
- `DTO/`：嵌套对象、数组、别名、验证、字段转换、`ApiVariable` 与 readonly 属性。
- `Enum/`：枚举 schema。
- `Model/`：`ActivityResponse::from()` 使用的模拟实体。

## 请求绑定

| 注解 | 用途 |
|------|------|
| `#[RequestBody]` | JSON 请求体；数组参数用 `@param DemoQuery[] $request` 声明元素类型 |
| `#[RequestQuery]` | 查询参数 DTO |
| `#[RequestHeader]` | 请求头 DTO |
| `#[RequestFormData]` | 表单 DTO，配合 `#[ApiFormData]` 补充文件字段 |

`#[Valid]` 执行 DTO 验证。控制器方法返回具体类可获得准确的响应 schema；`object`、`array`、`mixed` 只能生成通用结构。

## DTO 4.0 输入规则

- 输入只接受 primaryKey：有 `#[JSONField]` 时使用别名，否则使用 PHP 属性名。
- private/protected 属性需要公共 setter；readonly/static 属性不参与输入映射。
- 不可写的响应字段标记为 `readOnly`，`$ref` 使用 `allOf` 包裹以保留该标记。
- `#[Required]` 字段即使不可写也列为请求必填，因为验证先于映射执行。

## ApiVariable

通过响应实例指定可变字段类型：

```php
#[ApiResponse(new CodeResponse(PhpType::INT))]
public function codeInt(): CodeResponse { }
```

也支持 `types` 映射：

```php
#[ApiResponse(Page::class, 207, '分页数据', types: ['content' => [Address::class]])]
```

## 验证与生成

- `#[Required]` 写入 required 列表，`#[In]` 生成 enum；其他验证规则主要用于运行时校验。
- 数组元素验证使用 `#[Validation('integer', customKey: 'intArr.*')]`。
- OpenAPI 文件在启动时生成，修改注解后需重启。
- 升级 DTO 4.0 后，清理并重新生成 `runtime/container/proxy/` 中的代理。
