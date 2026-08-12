<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\GenerateParameters;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class GenerateParametersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * 路由占位符中的简单类型参数应生成为 path 必填.
     */
    public function testPathParamInRoute(): void
    {
        $result = $this->generate('/user/{id}', [
            $this->reflectionType('int', 'id'),
        ]);

        $this->assertCount(1, $result['parameter']);
        $this->assertSame('path', $result['parameter'][0]->in);
        $this->assertTrue($result['parameter'][0]->required);
        $this->assertSame('id', $result['parameter'][0]->name);
    }

    /**
     * 带正则约束的占位符 {id:\d+} 也应识别为 path 参数.
     */
    public function testPathParamWithRegexConstraint(): void
    {
        $result = $this->generate('/user/{id:\d+}', [
            $this->reflectionType('int', 'id'),
        ]);

        $this->assertSame('path', $result['parameter'][0]->in);
        $this->assertTrue($result['parameter'][0]->required);
    }

    private function reflectionType(string $type, string $name, bool $allowsNull = false, bool $defaultValueAvailable = false): ReflectionType
    {
        return new ReflectionType($type, $allowsNull, [
            'defaultValueAvailable' => $defaultValueAvailable,
            'defaultValue' => null,
            'name' => $name,
            'attributes' => [],
        ]);
    }

    private function generate(string $route, array $definitions): array
    {
        $swaggerCommon = new SwaggerCommon();
        $container = m::mock(ContainerInterface::class);
        $methodDefinitionCollector = m::mock(MethodDefinitionCollectorInterface::class);
        $methodDefinitionCollector->shouldReceive('getParameters')->andReturn($definitions);

        $generateParameters = new GenerateParameters(
            'DemoController',
            'list',
            [],
            [],
            $route,
            $container,
            $methodDefinitionCollector,
            new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null),
            $swaggerCommon,
            new PropertyManager($swaggerCommon, new PropertyEnum()),
            m::mock(MethodParametersManager::class),
        );
        return $generateParameters->generate();
    }
}
