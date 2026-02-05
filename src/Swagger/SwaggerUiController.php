<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Listener\BootAppRouteListener;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Api(hidden: true)]
class SwaggerUiController extends SwaggerController
{
    protected string $swaggerUiPath = BASE_PATH . '/vendor/tangwei/knife4j-ui/dist';

    public function swagger(): PsrResponseInterface
    {
        $filePath = $this->docsWebPath . '/swagger.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$prefixUrl}}', $this->swaggerConfig->getPrefixUrl(), $contents);
        $contents = str_replace('{{$path}}', $this->swaggerConfig->getPrefixSwaggerResources(), $contents);
        // $contents = str_replace('{{$url}}', $this->getSwaggerFileUrl(BootAppRouteListener::$httpServerName), $contents);
        $serverNameAll = array_reverse($this->swaggerOpenApi->serverNameAll);
        $urls = '';
        foreach ($serverNameAll as $serverName) {
            $url = $this->getSwaggerFileUrl($serverName);
            $urls .= "{url: '{$url}', name: '{$serverName} server'},";
        }
        $contents = str_replace('"{{$urls}}"', $urls, $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function redoc(): PsrResponseInterface
    {
        $filePath = $this->docsWebPath . '/redoc.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$url}}', $this->getSwaggerFileUrl(BootAppRouteListener::$httpServerName), $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function rapidoc(): PsrResponseInterface
    {
        // https://rapidocweb.com/examples.html
        $filePath = $this->docsWebPath . '/rapidoc.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$url}}', BootAppRouteListener::$httpServerName . '.' . $this->swaggerConfig->getFormat(), $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function scalar(): PsrResponseInterface
    {
        // https://github.com/scalar/scalar
        $serverNameAll = array_reverse($this->swaggerOpenApi->serverNameAll);
        $urls = '';
        foreach ($serverNameAll as $serverName) {
            $url = $this->getSwaggerFileUrl($serverName);
            $urls .= "{url: '{$url}', title: '{$serverName} server'},";
        }
        $filePath = $this->docsWebPath . '/scalar.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('"{{$urls}}"', $urls, $contents);

        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function knife4j()
    {
        $filePath = $this->swaggerUiPath . '/doc.html';
        $contents = file_get_contents($filePath);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function swaggerResources()
    {
        $serverNameAll = array_reverse($this->swaggerOpenApi->serverNameAll);
        $urls = [];
        foreach ($serverNameAll as $serverName) {
            $urls[] = [
                'name' => "{$serverName} server",
                'url' => $serverName . '.' . $this->swaggerConfig->getFormat(),
            ];
        }

        return $urls;
    }

    /**
     * 适配knife4j 4.5.0版本.
     * https://gitee.com/xiaoym/knife4j/issues/I986E2
     */
    public function swaggerConfig(): array
    {
        $urls = $this->swaggerResources();
        $data['urls'] = $urls;
        return $data;
    }

    public function knife4jFile(string $file): PsrResponseInterface
    {
        $file = str_replace('..', '', $file);
        $file = '/webjars/' . $file;
        $file = $this->swaggerUiPath . '/' . $file;
        return $this->fileResponse($file);
    }

    public function favicon(): PsrResponseInterface
    {
        $file = $this->docsWebPath . '/favicon.png';
        return $this->fileResponse($file);
    }
}
