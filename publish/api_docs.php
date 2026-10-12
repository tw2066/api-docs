<?php

declare(strict_types=1);

use Hyperf\ApiDocs\DTO\GlobalResponse;
use function Hyperf\Support\env;

return [
    // 是否启动 swagger 服务，false 将不会启动 swagger 服务
    'enable' => env('APP_ENV') !== 'prod',
    // 生成 swagger 文件格式，支持 json 和 yaml
    'format' => 'json',
    // 生成 swagger 文件路径
    'output_dir' => BASE_PATH . '/runtime/container',
    // 生成代理类路径
    'proxy_dir' => BASE_PATH . '/runtime/container/proxy',
    // 设置路由前缀
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
    // 设置 swagger 资源路径，cdn 资源
    'prefix_swagger_resources' => 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.32.0',

    // 设置全局返回的代理类
    // 全局返回 如 [code => 200, data => null] 格式，设置后会全局生成对应文档
    // 配合 ApiVariable 注解使用，示例参考 GlobalResponse 类
    // 返回数据格式可以利用 AOP 统一返回
    // 'global_return_responses_class' => GlobalResponse::class,

    // 替换验证属性
    // 通过获取注解 ApiModelProperty 的值，来提供数据验证的提示信息
    'validation_custom_attributes' => true,

    // 设置 DTO 类默认值等级
    // 0 默认（不设置默认值）
    // 1 简单类型会设置默认值，复杂类型（带 ?）会设置 null
    //   简单类型默认值：int:0  float:0  string:''  bool:false  array:[]  mixed:null
    // 2 （慎用）包含等级 1，且复杂类型（联合类型除外）会设置 null
    'dto_default_value_level' => 0,
    // 全局 responses，映射到 ApiResponse 注解对象
    'responses' => [
        ['response' => 401, 'description' => 'Unauthorized'],
        ['response' => 500, 'description' => 'System error'],
    ],
    // swagger 的基础配置
    // 该属性会映射到 OpenAPI 对象
    'swagger' => [
        // 文档信息
        'info' => [
            'title' => 'API DOC',
            'version' => '0.1',
            'description' => 'Swagger api desc, API for LLM integration [LLM Usage Guide](/swagger/llms.txt)',
        ],
        // 服务地址列表
        'servers' => [
            [
                'url' => 'http://127.0.0.1:9501',
                'description' => 'OpenApi host',
            ],
        ],
        // 安全方案定义
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
        // 全局安全要求
        'security' => [
            ['Authorization' => []],
        ],
        // 额外的文档链接
        'externalDocs' => [
            'description' => 'Find out more about Swagger',
            'url' => 'https://github.com/tw2066/api-docs',
        ],
    ],
];
