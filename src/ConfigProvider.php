<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs;

use Hyperf\ApiDocs\Listener\AfterDTOStartListener;
use Hyperf\ApiDocs\Listener\BootAppConfListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'listeners' => [
                AfterDTOStartListener::class,
                BootAppConfListener::class
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for api-docs.',
                    'source' => __DIR__ . '/../publish/apidocs.php',
                    'destination' => BASE_PATH . '/config/autoload/apidocs.php',
                ],
            ],
        ];
    }
}
