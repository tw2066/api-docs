<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\Contract\ConfigInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SwaggerConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSetPrefixUrlNormalizesPath(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $config->setPrefixUrl('/swagger/');
        $this->assertEquals('/swagger', $config->getPrefixUrl());

        $config->setPrefixUrl('api-docs');
        $this->assertEquals('/api-docs', $config->getPrefixUrl());

        $config->setPrefixUrl('///multiple///slashes///');
        $this->assertEquals('/multiple///slashes', $config->getPrefixUrl());
    }

    public function testIsEnableDefaultsToFalse(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertFalse($config->isEnable());
    }

    public function testGetOutputDir(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn(['output_dir' => '/custom/output']);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('/custom/output', $config->getOutputDir());
    }

    public function testGetProxyDirFallsBackToDefault(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $expected = BASE_PATH . '/runtime/container/proxy/';
        $this->assertEquals($expected, $config->getProxyDir());
    }

    public function testSetProxyDirNormalizesPath(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $config->setProxyDir('/custom/proxy/');
        $this->assertEquals('/custom/proxy/', $config->getProxyDir());
    }

    public function testGetPrefixUrlFallsBackToDefault(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('swagger', $config->getPrefixUrl());
    }

    public function testIsValidationCustomAttributesDefaultsToFalse(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertFalse($config->isValidationCustomAttributes());
    }

    public function testGetResponsesDefaultsToEmptyArray(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals([], $config->getResponses());
    }

    public function testGetSwaggerDefaultsToEmptyArray(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals([], $config->getSwagger());
    }

    public function testGetResponsesCodeDefaultsTo200(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('200', $config->getResponsesCode());
    }

    public function testGetFormatDefaultsToJson(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('json', $config->getFormat());
    }

    public function testGetFormatReturnsYamlWhenConfigured(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn(['format' => 'yaml']);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('yaml', $config->getFormat());
    }

    public function testGetFormatAlwaysReturnsYamlForUnknownFormat(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn(['format' => 'xml']);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('yaml', $config->getFormat());
    }

    public function testGetPrefixSwaggerResources(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $expected = 'https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.27.1';
        $this->assertEquals($expected, $config->getPrefixSwaggerResources());
    }

    public function testGetGlobalReturnResponsesClassDefaultsToEmpty(): void
    {
        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn([]);

        $config = new SwaggerConfig($configInterface);

        $this->assertEquals('', $config->getGlobalReturnResponsesClass());
    }

    public function testFullConfiguration(): void
    {
        $configData = [
            'enable' => true,
            'output_dir' => '/var/swagger',
            'proxy_dir' => '/var/proxy',
            'prefix_url' => '/api-docs',
            'validation_custom_attributes' => true,
            'responses' => [
                ['response' => 401, 'description' => 'Unauthorized'],
            ],
            'swagger' => [
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0',
                ],
            ],
        ];

        $configInterface = m::mock(ConfigInterface::class);
        $configInterface->shouldReceive('get')->with('api_docs', null)->andReturn($configData);

        $config = new SwaggerConfig($configInterface);

        $this->assertTrue($config->isEnable());
        $this->assertEquals('/var/swagger', $config->getOutputDir());
        $this->assertEquals('/var/proxy/', $config->getProxyDir());
        $this->assertEquals('/api-docs', $config->getPrefixUrl());
        $this->assertTrue($config->isValidationCustomAttributes());
        $this->assertCount(1, $config->getResponses());
        $this->assertEquals('Test API', $config->getSwagger()['info']['title']);
    }
}