## PHP Swagger Api Docs
一个基于 [Hyperf](https://github.com/hyperf/hyperf) 框架的 DTO 及 swagger 文档生成组件

1.  请求参数映射到PHP类，根据类和注解自动生成Swagger文档
2.  借鉴Java的swagger、 DTO写法，根据依赖类直接生成文档
3.  文件上传文档暂不支持  PHP >= 7.4

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
    //json文件输出目录
    'output_dir' => BASE_PATH.'/runtime/swagger',
    // swagger 的基础配置
    'swagger' => [
        'swagger' => '2.0',
        'info' => [
            'description' => 'swagger api desc',
            'version' => '1.0.0',
            'title' => 'API DOC',
        ]，
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
[INFO] Process[metric.0] start.
[INFO] Process[crontab-dispatcher.0] start.

```

> 看到`Swagger Url`显示，表示生成成功，访问`/swagger`即可以看到swagger页面


### 3. 介绍

## 定义契约Contracts 
> 定义一个类，增加类型属性 实现`implements`即可
#### RequestBody
- Body参数
### RequestQuery
- GET参数生成
### RequestFormData
- 表单请求

> tip: 一个方法中，入参中RequestBody和RequestFormData不能同时共存

## 示例

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Request\DemoBodyRequest;
use App\DTO\Request\DemoFormData;
use App\DTO\Request\DemoQuery;
use Tang\ApiDocs\Annotation\Api;
use Tang\ApiDocs\Annotation\ApiOperation;
use App\DTO\Response\Contact;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;

/**
 * @Controller(prefix="/demo")
 * @Api(tag="demo管理")
 */
class DemoController
{
    /**
     * @ApiOperation(summary="查询")
     * @PostMapping(path="index")
     * @param DemoQuery $request
     * @return Contact
     */
    public function index(DemoQuery $request) : Contact
    {
        $contact = new Contact();
        var_dump($request);
        return $contact;
    }
    /**
     * @ApiOperation(summary="查询单条记录")
     * @GetMapping(path="find/{id}/and/{in}")
     */
    public function find(int $id,int $in) : array
    {
        return ['$id'=>$id,'$in'=>$in];
    }
    /**
     * @ApiOperation(summary="提交body数据和get参数")
     * @PostMapping(path="add")
     * @param DemoBodyRequest $request
     * @param DemoQuery $request2
     */
    public function add(DemoBodyRequest $request,DemoQuery $request2) :Contact
    {
        var_dump($request2);
        var_dump($request);
        return new Contact();
    }
    /**
     * @ApiOperation(summary="表单提交")
     * @PostMapping(path="fromData")
     * @param DemoFormData $formData
     * @return bool
     */
    public function fromData(DemoFormData $formData) : bool
    {
        var_dump($formData);
        return true;
    }
}
```
```php
<?php

namespace App\DTO\Request;

use Tang\ApiDocs\Annotation\ApiModelProperty;
use Tang\DTO\Contracts\RequestBody;

class DemoBodyRequest implements RequestBody
{

    /**
     * @ApiModelProperty(value="demo名称")
     */
    public ?string $demoName = null;

    /**
     * @ApiModelProperty(value="价格",required=true)
     */
    public float $price;

    /**
     * @ApiModelProperty(value="示例id",required=true)
     * @var int[]
     */
    public array $demoId;

    /**
     * 需要绝对路径
     * @ApiModelProperty(value="地址")
     * @var \App\DTO\Address[]
     */
    public array $addressArr;
}
```
> 其他DTO文件 test文件夹

## Swagger界面
![hMvJnQ](https://gitee.com/tw666/source/raw/master/img/swagger.png)

## 相关软件
