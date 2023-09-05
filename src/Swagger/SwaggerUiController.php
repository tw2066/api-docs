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
    protected string $swaggerUiPath = BASE_PATH . '/vendor/tangwei/knife4j-ui';

    public function swagger(): PsrResponseInterface
    {
        $filePath = $this->docsWebPath . '/swagger.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$prefixUrl}}', $this->swaggerConfig->getPrefixUrl(), $contents);
        $contents = str_replace('{{$path}}', $this->swaggerConfig->getPrefixSwaggerResources(), $contents);
        $contents = str_replace('{{$url}}', $this->getSwaggerFileUrl(BootAppRouteListener::$httpServerName), $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }


    public function redoc(): PsrResponseInterface
    {
        $filePath = $this->docsWebPath . '/redoc.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$url}}', $this->getSwaggerFileUrl(BootAppRouteListener::$httpServerName), $contents);
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
        return [
            [
                'name' => 'api-docs',
                'url' => BootAppRouteListener::$httpServerName . '.' . $this->swaggerConfig->getFormat(),
                //                "swaggerVersion" => "",
                //                "location" => ""
            ],
        ];
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
