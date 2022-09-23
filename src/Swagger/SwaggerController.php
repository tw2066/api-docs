<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\ApiDocs\Listener\BootAppRouteListener;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Api(hidden: true)]
class SwaggerController
{
    private string $swaggerUiPath = BASE_PATH . '/vendor/tangwei/swagger-ui/dist';

    private string $outputDir;

    private array $uiFileList;

    private array $jsonFileList;

    public function __construct(private SwaggerConfig $swaggerConfig,private ConfigInterface $config,private ResponseInterface $response)
    {
        $this->outputDir = $this->config->get('api_docs.output_dir');
        $this->uiFileList = scandir($this->swaggerUiPath);
        $this->jsonFileList = scandir($this->outputDir);
    }

    public function index(): PsrResponseInterface
    {
        $filePath = BASE_PATH . '/vendor/tangwei/apidocs/src/web/index.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$path}}', '.'.$this->swaggerConfig->getPrefixUrl(), $contents);
        $contents = str_replace('{{$url}}', '.'.$this->getJsonUrl(BootAppRouteListener::$httpServerName), $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function getFile(string $file): PsrResponseInterface
    {
        if (! in_array($file, $this->uiFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $file = $this->swaggerUiPath . '/' . $file;
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

    private function getJsonUrl($serverName): string
    {
        return $this->swaggerConfig->getPrefixUrl() . '/' . $serverName . '.json';
    }

    public function map()
    {
    }
}
