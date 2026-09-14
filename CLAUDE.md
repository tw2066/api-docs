# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

`tangwei/apidocs` — 基于 Hyperf 的 Swagger/OpenAPI 3.x 文档自动生成组件。通过 PHP 8 Attributes 扫描控制器路由，在应用启动时生成 OpenAPI 描述文件，并内置多种文档 UI（Swagger UI、Knife4j、Redoc、RapiDoc、Scalar）及 llms.txt 输出。支持 Swoole / Swow / phar 部署。

## 常用命令

```bash
composer test                  # 运行全部测试（phpunit -c phpunit.xml）
vendor/bin/phpunit -c phpunit.xml --filter testMethodName tests/SwaggerPathsTest.php   # 运行单个测试
composer analyse               # PHPStan 静态分析（-l 0，仅 src/）
composer cs-fix                # php-cs-fixer 格式化 src 和 tests
```

CI 矩阵为 PHP 8.2/8.3/8.4 + Hyperf 3.2（pin `hyperf/di:3.2.*` + `tangwei/dto:dev-master`）。本包通过 `tangwei/dto ~3.2` 传递依赖 Hyperf ~3.2，不兼容 Hyperf 3.1。

## 架构核心

### 启动期生成流水线（理解本组件的关键）

OpenAPI 文件**不是请求时生成的**，而是在应用启动时由事件监听器驱动：

1. `BootAppRouteListener`（BootApplication 事件）— 在第一个 HTTP server 的路由上注册 `{prefix_url}` 路由组（UI 页面、`/webjars/*`、`{httpName}.json/yaml`、llms.txt 等），并把文档访问 URL 写入静态属性供 `AfterWorkerStartListener` 打印。
2. `AfterDtoStartListener`（`Hyperf\DTO\Event\AfterDtoStart` 事件，由 tangwei/dto 在扫描完路由后发出）— **每个 server 触发一次**：遍历该 server 的全部路由 Handler，对每个 `控制器@方法` 调 `SwaggerPaths::addPath()` 解析注解生成 `OA\PathItem`，最后 `SwaggerOpenApi::save()` 写入 `output_dir/{serverName}.json|yaml`。
3. `SwaggerOpenApi` 是**按 server 累积状态**的构建器：`init(serverName)` 重置 → 各 Generate 类向其 SplPriorityQueue（paths/tags 按 position 排序）投递 → `save()` 落盘 → `clean()` 释放。多 server 应用会为每个 server 各生成一份文件。

**注意**：`dtoConfig->isScanCacheable()` 为 true 时 `AfterDtoStartListener` 跳过生成（第 56-58 行提前 return）——扫描缓存模式下运行环境可能没有 output_dir 中的文件。

### 注解 → OpenAPI 的转换链

- `SwaggerPaths::addPath()` 读取类/方法注解（`#[Api]`、`#[ApiOperation]`、`#[ApiHeader]`、`#[ApiResponse]`、`#[ApiFormData]`、`#[ApiSecurity]`），委托给：
  - `GenerateParameters` — 从方法签名 + DTO 类生成 parameters/requestBody
  - `GenerateResponses` — 从方法**返回类型**（`MethodDefinitionCollector`）+ `#[ApiResponse]` + 全局 `GlobalResponse` 配置生成 responses；控制器方法返回具体类才能获得准确文档
  - `SwaggerComponents` — DTO 类的 `#[ApiModelProperty]`/验证注解 → `components.schemas`，继承自 tangwei/dto 的 `PropertyManager`
- `SwaggerConfig` 用 JsonMapper（`bIgnoreVisibility`）把 `config/autoload/api_docs.php` 直接映射到私有属性——**配置键名必须与属性名一致**（snake_case），新增配置项 = 新增同名私有属性。

### ApiVariable 代理类机制

`#[ApiVariable]` 标记的 DTO 属性（类型在运行时才能确定的"可变类型"）由 `GenerateProxyClass` 在运行时通过 PHP-Parser 重写原类 AST（`Ast\ResponseVisitor` 替换属性类型和命名空间为 `ApiDocs\Proxy`），写入 `proxy_dir`（默认 `runtime/container/proxy/`）供 schema 生成使用。

### 文件服务端点

`SwaggerController`（json/yaml/md/静态文件）和 `SwaggerUiController`（各 UI 页面 + knife4j webjars）按请求实例化。静态资源路径硬编码指向 `vendor/tangwei/swagger-ui/dist` 和 `vendor/tangwei/knife4j-ui/dist`（knife4j-ui 是 suggest 依赖，未安装时相关路由会 500）。三类端点校验方式不同：`getFile` 用 scandir 白名单精确匹配，`knife4jFile` 用 sanitize + realpath 前缀校验（嵌套路径无法白名单）。`fileResponse` 在 Swoole 下用 `SwooleFileStream`（sendfile），Swow/phar 下退回 `file_get_contents`。

### 与 tangwei/dto 的关系

本组件重度依赖 `tangwei/dto`（`Hyperf\DTO\*`）：注解扫描（`ApiAnnotation::classMetadata`）、DTO 验证、属性管理、Mapper 均来自该包。修改扫描/注解相关行为时，先确认逻辑在本包还是 dto 包。

## 测试约定

- 测试基类 `SwaggerUiControllerTestable` 重写了构造函数且**不调 `parent::__construct`**——父类构造函数的逻辑（目录检查、scandir）在测试中不会被覆盖到。
- `tests/Request/` 下的 DTO 是多个测试共用的 fixture。
- CI 在 hyperf/hyperf 容器镜像中运行，本地无 Swoole 也可跑 phpunit（测试不依赖 server 启动）。

## 示例与文档

- `example/` 目录是注解用法的活文档（各参数注解、分页、枚举、递归类型的完整示例），改注解行为时对照它验证。
- README.md / README_EN.md 需保持同步；环境要求以 composer.json 为准（README 中的版本号容易滞后）。
