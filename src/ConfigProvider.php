<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs;

use Hyperf\ApiDocs\Listener\AfterDtoStartListener;
use Hyperf\ApiDocs\Listener\BootAppRouteListener;
use Hyperf\DTO\Scan\ScanAnnotation;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'listeners' => [
                AfterDtoStartListener::class,
                BootAppRouteListener::class,
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map'          => [
                        ScanAnnotation::class => BASE_PATH . '/vendor/tangwei/apidocs/src/ClassMap/ScanAnnotation.php'
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for api-docs.',
                    'source' => __DIR__ . '/../publish/api_docs.php',
                    'destination' => BASE_PATH . '/config/autoload/api_docs.php',
                ],
            ],
        ];
    }
}
