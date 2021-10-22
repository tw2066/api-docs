<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;

#[Api(hidden: true)]
class SwaggerController
{
    private ConfigInterface $config;

    private string $outputDir;

    private array $uiFileList;

    private array $jsonFileList;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->outputDir = $this->config->get('api_docs.output_dir');
        $this->uiFileList = scandir(SwaggerRoute::getPath());
        $this->jsonFileList = scandir($this->outputDir);
    }

    public function index(): ResponseInterface
    {
        $file = SwaggerRoute::getPath() . '/index.html';
        $contents = file_get_contents($file);
        $contents = str_replace('{{$path}}', SwaggerRoute::getPrefix(), $contents);
        $contents = str_replace('{{$url}}', SwaggerRoute::getJsonUrl(SwaggerRoute::getHttpServerName()), $contents);
        $response = Context::get(ResponseInterface::class);
        return $response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function getFile(string $file): ResponseInterface
    {
        if (! in_array($file, $this->uiFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $file = SwaggerRoute::getPath() . '/' . $file;
        $response = Context::get(ResponseInterface::class);
        return $response->withBody(new SwooleFileStream($file));
    }

    public function getJsonFile(string $httpName): ResponseInterface
    {
        $file = $httpName . '.json';
        if (! in_array($file, $this->jsonFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $filePath = $this->outputDir . '/' . $file;
        $response = Context::get(ResponseInterface::class);
        return $response->withBody(new SwooleFileStream($filePath));
    }

    public function map()
    {
    }
}
