<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\HttpServer\Router\RouteCollector;

class SwaggerRoute
{
    private static string $path = __DIR__ . '/../../swagger-ui';

    private static string $prefix;

    private static string $httpServerName;

    public function __construct(string $prefix, string $httpServerName)
    {
        static::$prefix = '/' . trim($prefix, '//');
        static::$httpServerName = $httpServerName;
    }

    public static function getPath(): string
    {
        return static::$path;
    }

    public static function getPrefix(): string
    {
        return static::$prefix;
    }

    public static function getHttpServerName(): string
    {
        return static::$httpServerName;
    }

    public function add(RouteCollector $route)
    {
        $route->addGroup(static::getPrefix(), function ($route) {
            $route->get('', [SwaggerController::class, 'index']);
            $route->get('/{httpName}.json', [SwaggerController::class, 'getJsonFile']);
            $route->get('/{file}.map', [SwaggerController::class, 'map']);
            $route->get('/{file}', [SwaggerController::class, 'getFile']);
        });
    }

    public static function getJsonUrl($serverName): string
    {
        return static::getPrefix() . '/' . $serverName . '.json';
    }
}
