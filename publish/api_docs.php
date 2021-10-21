<?php

declare(strict_types=1);
return [
    // enable false 将不会启动 swagger 服务
    'enable' => env('APP_ENV') !== 'prod',
    'output_dir' => BASE_PATH . '/runtime/swagger',
    'prefix_url' => env('API_DOCS_PREFIX_URL','/swagger'),
    //认证api key
    'security_api_key' => ['Authorization'],
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
