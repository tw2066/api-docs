<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\ApiDocs\Listener\BootAppRouteListener;
use Hyperf\Engine\Constant;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Swow\Psr7\Message\BufferStream;

#[Api(hidden: true)]
class SwaggerController
{
    protected string $swaggerUiPath = BASE_PATH . '/vendor/tangwei/swagger-ui/dist';

    protected string $outputDir;

    protected array $uiFileList;

    protected array $swaggerFileList;

    public function __construct(protected SwaggerConfig $swaggerConfig, protected ResponseInterface $response)
    {
        $this->outputDir = $this->swaggerConfig->getOutputDir();
        $this->uiFileList = scandir($this->swaggerUiPath);
        $this->swaggerFileList = scandir($this->outputDir);
    }

    public function index(): PsrResponseInterface
    {
        $filePath = BASE_PATH . '/vendor/tangwei/apidocs/src/web/index.html';
        $contents = file_get_contents($filePath);
        $contents = str_replace('{{$path}}', $this->swaggerConfig->getPrefixResources(), $contents);
        $contents = str_replace('{{$url}}', $this->getSwaggerFileUrl(BootAppRouteListener::$httpServerName), $contents);
        return $this->response->withAddedHeader('content-type', 'text/html')->withBody(new SwooleStream($contents));
    }

    public function getFile(string $file): PsrResponseInterface
    {
        if (! in_array($file, $this->uiFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $file = $this->swaggerUiPath . '/' . $file;
        return $this->fileResponse($file);
    }

    public function getJsonFile(string $httpName): PsrResponseInterface
    {
        $file = $httpName . '.json';
        if (! in_array($file, $this->swaggerFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $filePath = $this->outputDir . '/' . $file;
        return $this->fileResponse($filePath);
    }

    public function getYamlFile(string $httpName): PsrResponseInterface
    {
        $file = $httpName . '.yaml';
        if (! in_array($file, $this->swaggerFileList)) {
            throw new ApiDocsException('File does not exist');
        }
        $filePath = $this->outputDir . '/' . $file;
        return $this->fileResponse($filePath);
    }

    protected function fileResponse(string $filePath)
    {
        if (Constant::ENGINE == 'Swoole') {
            $stream = new SwooleFileStream($filePath);
        } elseif (Constant::ENGINE == 'Swow') {
            /* @phpstan-ignore-next-line */
            $stream = new BufferStream(file_get_contents($filePath));
        } else {
            $stream = new SwooleStream(file_get_contents($filePath));
        }
        $response = $this->response->withBody($stream);

        $pathinfo = pathinfo($filePath);
        switch ($pathinfo['extension']) {
            case 'js':
            case 'map':
                $response = $response->withAddedHeader('content-type', 'application/javascript')->withAddedHeader('cache-control', 'max-age=43200');
                break;
            case 'css':
                $response = $response->withAddedHeader('content-type', 'text/css')->withAddedHeader('cache-control', 'max-age=43200');
                break;
        }
        return $response;
    }

    protected function getSwaggerFileUrl($serverName): string
    {
        return $this->swaggerConfig->getPrefixUrl() . '/' . $serverName . '.' . $this->swaggerConfig->getFormat();
    }
}
