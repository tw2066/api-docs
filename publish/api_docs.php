<?php

declare(strict_types=1);

return [
    // enable false 将不会启动 swagger 服务
    'enable' => env('APP_ENV') !== 'prod',
    'output_dir' => BASE_PATH . '/runtime/swagger',
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
    // 替换验证属性
    'validation_custom_attributes' => true,
    // 设置全局安全验证 可以与ApiSecurity注解配合使用
    'security' => [
        ['Authorization' => []],
    ],
    // 全局responses
    'responses' => [
        ['code'=>'401','description' => 'Unauthorized'],
        ['code'=>'500','description' => 'System error'],
    ],
    // swagger 的基础配置
    'swagger' => [
        'info' => [
            'title' => 'API DOC',
            'version' => '0.1',
            'description' => 'swagger api desc',
        ],
        'servers' => [
            [
                'url' => 'http://127.0.0.1:9501',
                'description' => 'OpenApi host',
            ],
        ],
        'components' => [
            'securitySchemes' => [
                [
                    'securityScheme' => 'Authorization',
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'Authorization',
                ],
                /* [
                     'securityScheme' => 'petstore_auth',
                     'type' => 'oauth2',
                     'flows' => [
                         [
                             'flow' => 'implicit',
                             'authorizationUrl' => 'http://petstore.swagger.io/oauth/dialog',
                             'scopes' => [
                                 'read:pets' => 'read your pets',
                                 'write:pets' => 'modify pets in your account',
                             ],
                         ],
                     ],
                 ],*/
            ],
        ],
        'externalDocs' => [
            'description' => 'Find out more about Swagger',
            'url' => 'https://github.com/tw2066/api-docs',
        ],
    ],
];
