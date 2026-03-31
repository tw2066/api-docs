<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Engine\Constant;
use Hyperf\HttpMessage\Stream\SwooleFileStream;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Phar;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Swow\Psr7\Message\BufferStream;

#[Api(hidden: true)]
class SwaggerController
{
    protected string $swaggerUiPath = BASE_PATH . '/vendor/tangwei/swagger-ui/dist';

    protected string $docsWebPath = BASE_PATH . '/vendor/tangwei/apidocs/src/web';

    protected string $outputDir;

    protected array $uiFileList;

    protected array $swaggerFileList;

    public function __construct(
        protected SwaggerConfig $swaggerConfig,
        protected ResponseInterface $response,
        protected SwaggerOpenApi $swaggerOpenApi,
        protected SwaggerLlms $swaggerLlms,
    ) {
        $this->outputDir = $this->swaggerConfig->getOutputDir();
        $this->uiFileList = is_dir($this->swaggerUiPath) ? scandir($this->swaggerUiPath) : [];
        $this->swaggerFileList = scandir($this->outputDir);
    }

    public function getFile(string $file): PsrResponseInterface
    {
        if (! in_array($file, $this->uiFileList)) {
            throw ApiDocsException::fileNotFound($file);
        }
        $file = $this->swaggerUiPath . '/' . $file;
        return $this->fileResponse($file);
    }

    public function getJsonFile(string $httpName): PsrResponseInterface
    {
        $file = $httpName . '.json';
        if (! in_array($file, $this->swaggerFileList)) {
            throw ApiDocsException::fileNotFound($file);
        }
        $filePath = $this->outputDir . '/' . $file;
        return $this->fileResponse($filePath)->withHeader('content-type', 'application/json;charset=utf-8');
    }

    public function getYamlFile(string $httpName): PsrResponseInterface
    {
        $file = $httpName . '.yaml';
        if (! in_array($file, $this->swaggerFileList)) {
            throw ApiDocsException::fileNotFound($file);
        }
        $filePath = $this->outputDir . '/' . $file;
        return $this->fileResponse($filePath)->withHeader('content-type', 'text/yaml;charset=utf-8');
    }

    public function llmsMd(string $httpName = 'http'): PsrResponseInterface
    {
        $prefix = $this->swaggerConfig->getPrefixUrl();
        $url = $this->swaggerConfig->getSwagger()['servers'][0]['url'] ?? '';
        if ($url) {
            $prefix = $url . $prefix;
        }
        $file = $httpName . '.json';
        if (! in_array($file, $this->swaggerFileList)) {
            throw ApiDocsException::fileNotFound($file);
        }
        $filePath = $this->outputDir . '/' . $file;
        $content = $this->swaggerLlms->list($httpName, $filePath, $prefix);
        return $this->response->raw($content);
    }

    public function llmsDetailMd(string $httpName, string $operationId): PsrResponseInterface
    {
        $file = $httpName . '.json';
        if (! in_array($file, $this->swaggerFileList)) {
            throw ApiDocsException::fileNotFound($file);
        }
        $filePath = $this->outputDir . '/' . $file;
        $content = $this->swaggerLlms->detail($operationId, $filePath);
        return $this->response->raw($content);
    }

    protected function fileResponse(string $filePath)
    {
        if (! $this->pharRunning() && Constant::ENGINE == 'Swoole') {  // phar报错
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

    private function pharRunning(): bool
    {
        return class_exists('Phar') && Phar::running();
    }
}
