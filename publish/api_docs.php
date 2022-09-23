<?php

declare(strict_types=1);

use Hyperf\ApiDocs\Parsing\Swagger2Parsing;

return [
    // 是否启动
    'enable' => env('APP_ENV') !== 'prod',
    // 默认解析器
    'default_parsing' => Swagger2Parsing::class,
    'output_dir' => BASE_PATH . '/runtime/swagger',
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
    // 启用默认安全验证
    'enable_default_security' => true,
    // 认证api
    'security_api' => [
        'Authorization' => ['in' => 'header', 'type' => 'apiKey'],
    ],
    // 替换验证属性
    'validation_custom_attributes' => false,
    // global responses
    'responses' => [
        // 500 => ['description' => 'System error'],
    ],
    // swagger basic configuration
    'swagger' => [
        'swagger' => '2.0',
        'info' => [
            'description' => 'swagger api desc',
            'version' => '1.0.0',
            'title' => 'API DOC',
        ],
        'basePath' => '/',
        'schemes' => [
            'http',
            'https',
        ],
        'host' => env('API_DOCS_HOST', ''),
    ],
];
