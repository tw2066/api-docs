<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\ApiDocs\Swagger\SwaggerLlms;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SwaggerLlmsTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        $this->tempFiles = [];
    }

    public function testEmptySchemaObjectIsDumpedAsYamlMap(): void
    {
        $method = new \ReflectionMethod(SwaggerLlms::class, 'dumpYaml');
        $method->setAccessible(true);

        $yaml = $method->invoke(new SwaggerLlms(), ['schema' => ['items' => []]]);

        $this->assertMatchesRegularExpression('/items:\s*\{\s*\}/', $yaml);
        $this->assertStringNotContainsString('items: []', $yaml);
    }

    public function testListGroupsByTagAndIncludesMethodAndPath(): void
    {
        $file = $this->createOpenApiFile();

        $content = (new SwaggerLlms())->list('http', $file, 'http://localhost/swagger');

        $this->assertStringContainsString("# Demo API\n\n> Demo API\n\n", $content);
        $this->assertStringContainsString('## User', $content);
        $this->assertStringContainsString(
            '- GET /users/{id} — [Get user](http://localhost/swagger/http/usersIdGet.md): First line Second line',
            $content
        );
        // 无 summary 时回退到 operationId
        $this->assertStringContainsString(
            '- POST /orders — [ordersPost](http://localhost/swagger/http/ordersPost.md)',
            $content
        );
        // PathItem 级非操作字段不应产生条目
        $this->assertStringNotContainsString('[parameters]', $content);
        $this->assertLessThan(strpos($content, '## Order'), strpos($content, '## User'));
    }

    public function testDetailKeepsOnlyRequestedOperationAndSecuritySchemes(): void
    {
        $file = $this->createOpenApiFile();

        $content = (new SwaggerLlms())->detail('usersIdGet', $file);

        $this->assertStringContainsString("# Get user\n\n`GET /users/{id}`\n\n", $content);
        $this->assertStringContainsString('/users/{id}', $content);
        $this->assertStringContainsString('operationId: usersIdGet', $content);
        $this->assertStringContainsString('securitySchemes:', $content);
        $this->assertStringContainsString('Authorization:', $content);
        $this->assertStringContainsString('User:', $content);
        $this->assertStringNotContainsString('/orders', $content);
        $this->assertStringNotContainsString('externalDocs', $content);
    }

    public function testDetailThrowsWhenOperationIdNotExist(): void
    {
        $file = $this->createOpenApiFile();

        $this->expectException(ApiDocsException::class);
        $this->expectExceptionMessage('Operation not found: notExistGet');

        (new SwaggerLlms())->detail('notExistGet', $file);
    }

    public function testGetSchemasHandlesCircularReference(): void
    {
        $components = ['schemas' => [
            'Node' => ['type' => 'object', 'properties' => ['child' => ['$ref' => '#/components/schemas/Node']]],
        ]];

        $method = new \ReflectionMethod(SwaggerLlms::class, 'getSchemas');
        $method->setAccessible(true);
        $schemas = $method->invoke(new SwaggerLlms(), ['$ref' => '#/components/schemas/Node'], $components);

        $this->assertSame(['Node'], $schemas);
    }

    public function testGetSchemasSkipsUndefinedSchema(): void
    {
        $method = new \ReflectionMethod(SwaggerLlms::class, 'getSchemas');
        $method->setAccessible(true);
        $schemas = $method->invoke(new SwaggerLlms(), ['$ref' => '#/components/schemas/Missing'], []);

        $this->assertSame(['Missing'], $schemas);
    }

    private function createOpenApiFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'apidocs_');
        file_put_contents($file, json_encode([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Demo API', 'description' => "Demo\nAPI", 'version' => '1.0.0'],
            'servers' => [['url' => 'http://127.0.0.1:9501']],
            'paths' => [
                '/users/{id}' => [
                    'get' => [
                        'tags' => ['User'],
                        'summary' => 'Get user',
                        'description' => "First line\nSecond line",
                        'operationId' => 'usersIdGet',
                        'responses' => [
                            '200' => [
                                'description' => 'ok',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/User']]],
                            ],
                        ],
                    ],
                    // PathItem 级非操作字段
                    'parameters' => [['name' => 'id', 'in' => 'path']],
                ],
                '/orders' => [
                    'post' => [
                        'tags' => ['Order'],
                        'operationId' => 'ordersPost',
                        'responses' => ['200' => ['description' => 'ok']],
                    ],
                ],
            ],
            'components' => [
                'schemas' => ['User' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]]],
                'securitySchemes' => ['Authorization' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'Authorization']],
            ],
            'tags' => [['name' => 'User'], ['name' => 'Order']],
            'externalDocs' => ['url' => 'https://example.com'],
        ], JSON_UNESCAPED_UNICODE));
        $this->tempFiles[] = $file;
        return $file;
    }
}
