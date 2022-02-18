<?php

declare(strict_types=1);
return [
    // enable false 将不会启动 swagger 服务
    'enable'           => env('APP_ENV') !== 'prod',
    'output_dir'       => BASE_PATH . '/runtime/swagger',
    'prefix_url'       => env('API_DOCS_PREFIX_URL', '/swagger'),
    //认证api key
    // type ege: header, query, cookie
//    'security_api_key' => ['Authorization', 'query' => 'token'],
    'security_api_key' => ['Authorization'],
    //全局responses
    'responses'        => [
        401 => ['description' => 'Unauthorized'],
    ],
    // swagger 的基础配置
    'swagger'          => [
//        'openapi' => '3.0.2',
//        'servers' => [
//            [
//                'url'         => env('API_DOCS_HOST', ''),
//                'description' => '本地环境',
//            ],
//        ],
        'swagger' => '2.0',
        'host'    => env('API_DOCS_HOST',''),
        'info'    => [
            'description' => 'swagger api desc',
            'version'     => '1.0.0',
            'title'       => 'API DOC',
        ],
    ],
    'templates'        => [
        'success' => [
            "code|状态" => '0',
            "data|数据" => '{template}',
            "msg|信息"  => 'Success',
        ],
        'page'    => [
            "code|状态" => '0',
            "data|数据" => [
                'page_size|每页个数' => 10,
                'total|总数'       => 1,
                'page|页码'        => 1,
                'list|列表'        => '{template}',
            ],
            "msg|信息"  => 'Success',
        ],
    ],
];
