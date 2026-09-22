<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Annotation\ApiVariable;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\ApiDocs\Swagger\GenerateProxyClass;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\DTO\DtoConfig;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\Page;
use HyperfTest\ApiDocs\Request\User;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class GenerateProxyClassTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AnnotationCollector::collectProperty(Page::class, 'content', ApiVariable::class, new ApiVariable());
    }

    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear(Page::class);
    }

    /**
     * types映射写法与实例写法应产出相同的代理类.
     */
    public function testGenerateByTypesMatchesInstanceGenerate(): void
    {
        $proxy = $this->makeGenerateProxyClass();
        $byTypes = $proxy->generateByTypes(Page::class, ['content' => [Address::class]]);
        $this->assertSame('ApiDocs\Proxy\PageArrayAddress', $byTypes);

        $byInstance = $proxy->generate(new Page([new Address()]));
        $this->assertSame($byTypes, $byInstance);
    }

    public function testGenerateByTypesWithClassNameValue(): void
    {
        $proxy = $this->makeGenerateProxyClass();
        $proxyClass = $proxy->generateByTypes(Page::class, ['content' => [User::class]]);
        $this->assertSame('ApiDocs\Proxy\PageArrayUser', $proxyClass);
    }

    public function testGenerateByTypesWithInvalidPropertyThrows(): void
    {
        $proxy = $this->makeGenerateProxyClass();
        $this->expectException(ApiDocsException::class);
        $proxy->generateByTypes(Page::class, ['notExist' => [Address::class]]);
    }

    public function testGenerateByTypesReturnsOriginWhenNotApplicable(): void
    {
        $proxy = $this->makeGenerateProxyClass();
        $this->assertSame(Page::class, $proxy->generateByTypes(Page::class, []));
        // 无ApiVariable属性的类直接返回原类名
        $this->assertSame(Address::class, $proxy->generateByTypes(Address::class, ['name' => 'string']));
    }

    public function testApiResponseTypesValidation(): void
    {
        $apiResponse = new ApiResponse(Page::class, 200, 'ok', ['content' => [Address::class]]);
        $this->assertSame(['content' => [Address::class]], $apiResponse->types);

        $this->expectException(ApiDocsException::class);
        new ApiResponse(Page::class, 200, 'ok', ['content' => [123]]);
    }

    public function testApiResponseTypesKeyMustBeString(): void
    {
        $this->expectException(ApiDocsException::class);
        new ApiResponse(Page::class, 200, 'ok', [123 => Address::class]);
    }

    private function makeGenerateProxyClass(): GenerateProxyClass
    {
        $swaggerConfig = m::mock(SwaggerConfig::class);
        $swaggerConfig->shouldReceive('getProxyDir')->andReturn(sys_get_temp_dir() . '/api_docs_proxy_test/');
        $dtoConfig = m::mock(DtoConfig::class);
        $dtoConfig->shouldReceive('isScanCacheable')->andReturn(false);
        return new GenerateProxyClass($swaggerConfig, new SwaggerCommon(), $dtoConfig);
    }
}
