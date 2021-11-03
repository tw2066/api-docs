<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Api(hidden: true)]
class SwaggerController
{
    private ConfigInterface $config;

    private string $outputDir;

    private array $uiFileList;

    private array $jsonFileList;

    private ResponseInterface $response;

    public function __construct(ConfigInterface $config, ResponseInterface $response)
    {
        $this->config = $config;
        $this->outputDir = $this->config->get('api_docs.output_dir');
        $this->uiFileList = scandir(SwaggerRoute::getPath());
        $this->jsonFileList = scandir($this->outputDir);
        $this->response = $response;
    }

    public function index(): PsrResponseInterface
    {
        $file = SwaggerRoute::getPath() . '/index.html';
        $contents = file_get_contents($file);
        $contents = str_replace('{{$path}}', SwaggerRoute::getPrefix(), $contents);
        $contents = str_replace('{{$url}}', SwaggerRoute::getJsonUrl(SwaggerRoute::getHttpServerName()), $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function getFile(string $file): PsrResponseInterface
    {
        if (! in_array($file, $this->uiFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $file = SwaggerRoute::getPath() . '/' . $file;
        return $this->response->withBody(new SwooleFileStream($file));
    }

    public function getJsonFile(string $httpName): PsrResponseInterface
    {
        $file = $httpName . '.json';
        if (! in_array($file, $this->jsonFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $filePath = $this->outputDir . '/' . $file;
        return $this->response->withBody(new SwooleFileStream($filePath));
    }

    public function map()
    {
    }
}
