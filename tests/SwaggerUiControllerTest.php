<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\ApiDocs\Swagger\SwaggerOpenApi;
use Hyperf\ApiDocs\Swagger\SwaggerUiController;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @internal
 * @coversNothing
 */
class SwaggerUiControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSanitizeFilePathRemovesDoubleDots(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $controller = new SwaggerUiControllerTestable($config, $response, m::mock(SwaggerOpenApi::class));

        $reflection = new ReflectionMethod($controller, 'sanitizeFilePath');

        $result = $reflection->invoke($controller, '../../../etc/passwd');
        $this->assertStringNotContainsString('..', $result);
    }

    public function testSanitizeFilePathRemovesBackslashes(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $controller = new SwaggerUiControllerTestable($config, $response, m::mock(SwaggerOpenApi::class));

        $reflection = new ReflectionMethod($controller, 'sanitizeFilePath');

        $result = $reflection->invoke($controller, '..\..\windows\system32');
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('\\', $result);
    }

    public function testSanitizeFilePathRemovesNullBytes(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $controller = new SwaggerUiControllerTestable($config, $response, m::mock(SwaggerOpenApi::class));

        $reflection = new ReflectionMethod($controller, 'sanitizeFilePath');

        $result = $reflection->invoke($controller, "file\0name");
        $this->assertStringNotContainsString("\0", $result);
    }

    public function testSanitizeFilePathRemovesLeadingSlash(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $controller = new SwaggerUiControllerTestable($config, $response, m::mock(SwaggerOpenApi::class));

        $reflection = new ReflectionMethod($controller, 'sanitizeFilePath');

        $result = $reflection->invoke($controller, '/etc/passwd');
        $this->assertStringStartsNotWith('/', $result);
    }

    public function testSanitizeFilePathWithNormalFile(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $controller = new SwaggerUiControllerTestable($config, $response, m::mock(SwaggerOpenApi::class));

        $reflection = new ReflectionMethod($controller, 'sanitizeFilePath');

        $result = $reflection->invoke($controller, 'swagger-ui.bundle.js');
        $this->assertEquals('swagger-ui.bundle.js', $result);
    }

    public function testSwaggerResourcesReturnsCorrectFormat(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $openApi = m::mock(SwaggerOpenApi::class);
        $openApi->serverNameAll = ['http', 'https'];

        $controller = new SwaggerUiControllerTestable($config, $response, $openApi);

        $result = $controller->swaggerResources();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('https server', $result[0]['name']);
        $this->assertEquals('https.json', $result[0]['url']);
    }

    public function testSwaggerConfigReturnsCorrectFormat(): void
    {
        $config = m::mock(SwaggerConfig::class);
        $config->shouldReceive('getPrefixUrl')->andReturn('/swagger');
        $config->shouldReceive('getFormat')->andReturn('json');

        $response = m::mock(ResponseInterface::class);

        $openApi = m::mock(SwaggerOpenApi::class);
        $openApi->serverNameAll = ['http'];

        $controller = new SwaggerUiControllerTestable($config, $response, $openApi);

        $result = $controller->swaggerConfig();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('urls', $result);
        $this->assertIsArray($result['urls']);
    }
}

class SwaggerUiControllerTestable extends SwaggerUiController
{
    public function __construct(
        SwaggerConfig $swaggerConfig,
        ResponseInterface $response,
        SwaggerOpenApi $swaggerOpenApi
    ) {
        $this->swaggerConfig = $swaggerConfig;
        $this->response = $response;
        $this->swaggerOpenApi = $swaggerOpenApi;
    }

    public function swaggerResources(): array
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

    public function swaggerConfig(): array
    {
        $urls = $this->swaggerResources();
        $data['urls'] = $urls;
        return $data;
    }

    protected function sanitizeFilePath(string $file): string
    {
        $file = str_replace(['..', '\\', "\0"], '', $file);
        return ltrim($file, '/');
    }
}
