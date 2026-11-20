# API Docs 4.0 升级说明

## 版本与依赖

4.0 的核心变化是依赖 **tangwei/dto 4.0**，文档生成随之适配 DTO 4.0 的类型与输入规则。

| 依赖 | 3.2.x | 4.0 |
|---|---|---|
| `php` | `>=8.2` | `>=8.1` |
| `tangwei/dto` | `~3.2.0` | `~4.0.0` |
| `zircote/swagger-php` | `^6.0` | `^5.8\|^6.0` |
| `hyperf/*` | `~3.1\|~3.2` | `~3.1\|~3.2` |

PHP 最低版本放宽、swagger-php 约束放宽，均不是 breaking；**dto 4.0 的输入/验证变化才是升级重点**。

## 必须检查的 breaking change

### 1. DTO 输入与验证遵循 4.0 规则

- 输入只接受字段的 `primaryKey`：有 `#[JSONField]` 时使用外部别名，否则使用 PHP 属性名。
- 不再接受别名与属性名互换，也不再接受隐式 snake_case、kebab-case、setter 或大小写变体。
- 验证规则、messages、attributes 的默认键使用与请求一致的外部 primaryKey；`Same`、`RequiredIf` 等跨字段参数同样使用外部键。
- private/protected 属性需要正常业务 setter 才能接收输入；readonly/static 属性不参与输入映射。
- 带 `#[Required]` 的字段例外：验证先于映射执行，文档仍将其列为请求必填字段。

完整的输入映射、验证键、代理和 RPC 变化见 dto 的 [UPGRADE-4.0.md](https://github.com/tw2066/dto/blob/master/UPGRADE-4.0.md)。

### 2. 接口/抽象类属性不再因无法解析而抛异常

3.2.x 中，接口或抽象类属性没有可用的具体类时，文档生成会抛出 `typeResolutionFailed` 并中断启动。

4.0 改为通过 DTO `classMap` 解析：

- **静态类目标** → 生成具体类的 component schema；
- **callable / 运行时动态映射 / 未配置** → 降级为通用 `object` schema，不再中断启动期文档生成。

需要具体 schema 时，请配置静态 `classMap`：

```php
use Hyperf\DTO\Mapper;

Mapper::getJsonMapper()->classMap[PaymentInterface::class] = CardPayment::class;
```

### 3. 联合类型属性文档降级为 `object`

原生联合类型（如 `DateTime|string`、`FooA|FooB`）无法确定唯一 schema，4.0 统一输出通用 `object`，不再取联合类型的第一个成员。

映射侧同样按原值写入，由 PHP 原生联合类型兜底；非法值抛 `JsonMapper_Exception`。

### 4. `ApiResponse` 新增 `types` 参数

`#[ApiResponse]` 新增 `types`，用于 `#[ApiVariable]` 属性，无需实例化即可生成代理类：

```php
#[ApiResponse(Page::class, 200, '分页数据', types: ['content' => [UserResponse::class]])]
public function list(): Page
{
    // ...
}
```

- `types` 仅当 `returnType` 为**单个类名**时生效，不支持 `[Page::class]` 这类数组包裹形式。
- 值为类名字符串、`PhpType`、实例，或单元素数组（表示数组）。
- 与 `global_return_responses_class` 同时使用时，4.0 会先应用 `types` 再包裹全局响应，两者可以组合。

### 5. `JSONField`、可见性与默认值

- schema 的属性名与 `required` 使用外部别名；只用于响应的私有别名字段标记为 `readOnly`。
- 带 `#[Required]` 的私有/只读字段仍按请求必填输出。
- 数组属性不再写入 `default`（避免 Knife4j 在生成请求示例时把数组默认值序列化成字符串）。
- 未配置 classMap 的接口属性、联合类型属性统一为 `object`，不再输出首成员 schema。

### 6. 新增组件（非 breaking）

- **di-map.json**：新增 `DiMapGenerateListener`，启动时为 IDE 插件生成 DI 映射文件到 `api_docs.output_dir/di-map.json`；非协程 server 只在该 server 的 worker 0 执行。
- **AI Skill**：`php bin/hyperf.php vendor:publish tangwei/apidocs --id=skill` 发布 `.claude/skills/api-docs/SKILL.md`；组件升级后可用 `--force` 重新发布。
- **llms 详情页**：`/swagger/{httpName}/{operationId}.md` 的 YAML 输出修复了空列表字段（如 `security: []` 不再输出为 `security: {  }`）。

## 升级步骤

1. **升级依赖**（先 dto，再 api-docs；Composer 会一起解析）：

   ```bash
   composer require tangwei/dto:^4.0 tangwei/apidocs:^4.0
   ```

2. **按 dto 升级说明调整业务代码**：DTO 输入键、验证键、别名、setter/可见性；详见 [dto UPGRADE-4.0.md](https://github.com/tw2066/dto/blob/master/UPGRADE-4.0.md)。

3. **清理并重建代理**：删除 `runtime/container/proxy/` 下的 DTO / API Docs 代理文件，重启后自动生成。代理缓存只检查文件存在与源文件 mtime，不会因版本变化自动失效。

4. **开启扫描缓存时删除旧文档产物**：如果 `scan_cacheable=true` 且 `api_docs.output_dir` 下已存在 `{serverName}.json|yaml`，启动期会跳过文档生成。升级后请删除旧产物（或临时关闭扫描缓存）再启动，确保用 4.0 逻辑重新生成。

5. **重新发布配置与 Skill**（注意不要直接覆盖已定制的配置）：

   ```bash
   php bin/hyperf.php vendor:publish tangwei/apidocs
   php bin/hyperf.php vendor:publish tangwei/apidocs --id=skill --force
   ```

   4.0 的配置键与 3.2.x 一致，但注释和默认 CDN 版本有更新；发布后与项目现有配置做一次 diff 即可。

6. **检查 classMap 配置**：callable / 动态映射现在文档为 `object`；需要具体 schema 的接口/抽象类请改为静态类目标。

7. **检查 `#[ApiResponse]` 的 workaround**：如果之前为了在全局响应下表达 `types` 做了额外处理，4.0 已原生支持，可以移除。

## 验证清单

- 启动后确认 `api_docs.output_dir/{serverName}.json|yaml` 已重新生成，且包含预期的 `components.schemas`。
- 访问 `/swagger`、`/swagger/llms.txt`、`/swagger/{httpName}/{operationId}.md`。
- 用包含以下场景的 DTO 验证文档与请求行为一致：
  - 接口/抽象类属性（静态 classMap 与未配置两种）；
  - 联合类型属性；
  - `#[JSONField]` 别名、`#[Required]`、只读/私有字段；
  - `#[ApiResponse(..., types: ...)]` 与 `global_return_responses_class` 组合。
- 运行项目测试；如有 OpenAPI snapshot / 契约测试，更新基线。

## 兼容性说明

- **配置键未变化**：`enable`、`format`、`output_dir`、`proxy_dir`、`prefix_url`、`prefix_swagger_resources`、`global_return_responses_class`、`validation_custom_attributes`、`dto_default_value_level`、`responses`、`swagger`。
- `prefix_swagger_resources` 默认 CDN 从 `5.32.0` 升到 `5.34.1`；如项目自定义了 CDN，可保持原值。
- PHP 最低版本从 8.2 降到 8.1；swagger-php 支持 `^5.8` 或 `^6.0`。
