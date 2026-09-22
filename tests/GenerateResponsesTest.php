<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Swagger\GenerateProxyClass;
use Hyperf\ApiDocs\Swagger\GenerateResponses;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\Page;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class GenerateResponsesTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * 同状态码时，方法级 ApiResponse 注解应覆盖全局 responses 配置.
     */
    public function testAnnotationResponseOverridesGlobalResponse(): void
    {
        $swaggerConfig = m::mock(SwaggerConfig::class);
        $swaggerConfig->shouldReceive('getResponsesCode')->andReturn('200');
        $swaggerConfig->shouldReceive('getGlobalReturnResponsesClass')->andReturn('');
        $swaggerConfig->shouldReceive('getResponses')->andReturn([
            ['response' => 401, 'description' => 'Global Unauthorized'],
        ]);

        $apiResponse = new ApiResponse(null, 401, 'Annotation Unauthorized');

        $generateResponses = $this->makeGenerateResponses($swaggerConfig, [$apiResponse]);
        $responses = $generateResponses->generate();

        $resp401 = null;
        foreach ($responses as $response) {
            if ((int) $response->response === 401) {
                $resp401 = $response;
            }
        }
        $this->assertNotNull($resp401);
        $this->assertSame('Annotation Unauthorized', $resp401->description);
    }

    /**
     * 注解未覆盖的状态码仍使用全局配置.
     */
    public function testGlobalResponseKeptWhenNotOverridden(): void
    {
        $swaggerConfig = m::mock(SwaggerConfig::class);
        $swaggerConfig->shouldReceive('getResponsesCode')->andReturn('200');
        $swaggerConfig->shouldReceive('getGlobalReturnResponsesClass')->andReturn('');
        $swaggerConfig->shouldReceive('getResponses')->andReturn([
            ['response' => 500, 'description' => 'Global System Error'],
        ]);

        $generateResponses = $this->makeGenerateResponses($swaggerConfig, []);
        $responses = $generateResponses->generate();

        $descriptions = array_map(fn ($r) => $r->description, $responses);
        $this->assertContains('Global System Error', $descriptions);
    }

    /**
     * types属性映射写法应经generateByTypes生成代理类并输出对应schema.
     */
    public function testApiResponseWithTypes(): void
    {
        $swaggerConfig = m::mock(SwaggerConfig::class);
        $swaggerConfig->shouldReceive('getResponsesCode')->andReturn('200');
        $swaggerConfig->shouldReceive('getGlobalReturnResponsesClass')->andReturn('');
        $swaggerConfig->shouldReceive('getResponses')->andReturn([]);

        $proxy = m::mock(GenerateProxyClass::class);
        $proxy->shouldReceive('getApiVariableClass')->with(Page::class)->andReturn(['content']);
        $proxy->shouldReceive('getApiVariableClass')->with(m::any())->andReturn([]);
        $proxy->shouldReceive('generateByTypes')
            ->with(Page::class, ['content' => [Address::class]])
            ->andReturn(Address::class);

        $apiResponse = new ApiResponse(Page::class, 206, '分页数据', ['content' => [Address::class]]);

        $generateResponses = $this->makeGenerateResponses($swaggerConfig, [$apiResponse], $proxy, [Address::class]);
        $responses = $generateResponses->generate();

        $resp206 = null;
        foreach ($responses as $response) {
            if ((int) $response->response === 206) {
                $resp206 = $response;
            }
        }
        $this->assertNotNull($resp206);
        $this->assertSame('分页数据', $resp206->description);
        $this->assertSame('#/components/schemas/Address', $resp206->content['application/json']->schema->ref);
    }

    private function makeGenerateResponses(SwaggerConfig $swaggerConfig, array $apiResponseArr, ?GenerateProxyClass $proxy = null, array $containerHasClasses = []): GenerateResponses
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturnUsing(fn ($class) => in_array($class, $containerHasClasses, true));
        $container->shouldReceive('get')->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());

        $swaggerCommon = new SwaggerCommon();
        return new GenerateResponses(
            DemoBodyRequest::class,
            'getBo',
            $apiResponseArr,
            $swaggerConfig,
            new MethodDefinitionCollector(),
            $container,
            new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null),
            $swaggerCommon,
            $proxy ?? m::mock(GenerateProxyClass::class),
        );
    }
}
