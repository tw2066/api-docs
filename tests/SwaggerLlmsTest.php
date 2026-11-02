<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerLlms;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SwaggerLlmsTest extends TestCase
{
    public function testEmptySchemaObjectIsDumpedAsYamlMap(): void
    {
        $method = new \ReflectionMethod(SwaggerLlms::class, 'dumpYaml');
        $method->setAccessible(true);

        $yaml = $method->invoke(new SwaggerLlms(), ['schema' => ['items' => []]]);

        $this->assertMatchesRegularExpression('/items:\s*\{\s*\}/', $yaml);
        $this->assertStringNotContainsString('items: []', $yaml);
    }
}
