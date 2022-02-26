<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

class MainCollect
{
    public static array $mainInfo = [];

    public static array $tags = [];

    /**
     * @var RouteCollect[]
     */
    private static array $routes = [];

    public static function clean(): void
    {
        self::$mainInfo = [];
        self::$routes = [];
        self::$tags = [];
    }

    public static function getMainInfo(): array
    {
        return self::$mainInfo;
    }

    public static function setMainInfo(array $mainInfo): void
    {
        self::$mainInfo = $mainInfo;
    }

    /**
     * @return RouteCollect[]
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    public static function setRoutes(RouteCollect $routes): void
    {
        self::$routes[] = $routes;
    }

    /**
     * @return string[]
     */
    public static function getTags(): array
    {
        return self::$tags;
    }

    /**
     * @param string[] $tags
     */
    public static function setTags(string $tagName, array $tags): void
    {
        self::$tags[$tagName] = $tags;
    }
}
