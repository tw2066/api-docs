<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

class MainCollect
{
    public static array $mainInfo = [];

    /**
     * @var RouteCollect[]
     */
    private static array $routes = [];

    /**
     * @var array
     */
    public static array $tags = [];

    public static function clean(): void
    {
        self::$mainInfo = [];
        self::$routes = [];
        self::$tags = [];
    }



    /**
     * @return array
     */
    public static function getMainInfo(): array
    {
        return self::$mainInfo;
    }

    /**
     * @param array $mainInfo
     */
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

    /**
     * @param RouteCollect $routes
     */
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
    public static function setTags(string $tagName , array $tags): void
    {
        self::$tags[$tagName] = $tags;
    }

}
