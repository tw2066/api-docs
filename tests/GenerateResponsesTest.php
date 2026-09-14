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
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
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

    private function makeGenerateResponses(SwaggerConfig $swaggerConfig, array $apiResponseArr): GenerateResponses
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(false);
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
            m::mock(GenerateProxyClass::class),
        );
    }
}
