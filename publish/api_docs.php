<?php

declare(strict_types=1);

use Hyperf\ApiDocs\Parsing\Swagger2Parsing;

return [
    // enable false 将不会启动 swagger 服务
    'enable' => env('APP_ENV') !== 'prod',
    //默认解析器
    'default_parsing' =>  Swagger2Parsing::class,
    'output_dir' => BASE_PATH . '/runtime/swagger',
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
    //认证api key
    'security_api_key' => ['Authorization'],
    //替换验证属性
    'validation_custom_attributes' => false,
    'is_debug' => false,
    //全局responses
    'responses' => [
        401 => ['description' => 'Unauthorized'],
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
