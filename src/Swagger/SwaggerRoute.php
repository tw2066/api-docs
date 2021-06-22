<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Router\RouteCollector;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;

class SwaggerRoute
{
    private string $path;

    private string $prefix;

    private string $outputDir;

    public function __construct(string $prefix, string $outputDir)
    {
        $this->prefix = $prefix;
        $this->outputDir = $outputDir;
        $this->path = __DIR__ . '/../swagger-ui';
    }

    public function add(RouteCollector $route, $serverName)
    {
        $route->addRoute(['GET'], $this->prefix, function () use ($serverName) {
            $file = $this->path . '/index.html';
            $contents = file_get_contents($file);
            $contents = str_replace('{{$path}}', $this->prefix, $contents);
            $contents = str_replace('{{$url}}', $this->makeJsonUrl($serverName), $contents);
            $response = Context::get(ResponseInterface::class);
            return $response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
        });

        $fileArray = [
            '/swagger-ui.css',
            '/swagger-ui-bundle.js',
            '/swagger-ui-standalone-preset.js',
            '/favicon-32x32.png',
            '/favicon-16x16.png',
        ];
        foreach ($fileArray as $file) {
            $route->addRoute(['GET'], $this->prefix . $file, function () use ($file) {
                $file = $this->path . $file;
                $response = Context::get(ResponseInterface::class);
                return $response->withBody(new SwooleFileStream($file));
            });
            $route->addRoute(['GET'], $this->prefix . $file . '.map', function () {
            });
        }
    }

    public function addJson(RouteCollector $route, string $serverName, string $outputFile)
    {
        $route->addRoute(['GET'], $this->makeJsonUrl($serverName), function () use ($outputFile) {
            $response = Context::get(ResponseInterface::class);
            return $response->withAddedHeader('Content-Type', 'application/json')->withBody(new SwooleFileStream($outputFile));
        });
    }

    private function makeJsonUrl($serverName): string
    {
        return $this->prefix . '/' . $serverName . '.json';
    }
}
