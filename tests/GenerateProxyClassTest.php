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
use Hyperf\DTO\Type\PhpType;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\Page;
use HyperfTest\ApiDocs\Request\ProxyNullableDefaults;
use HyperfTest\ApiDocs\Request\ProxyWithHelper;
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
        AnnotationCollector::clear();
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

    public function testGenerateProxyKeepsHelperClassName(): void
    {
        (new ApiVariable())->collectProperty(ProxyWithHelper::class, 'content');
        $proxy = $this->makeGenerateProxyClass();
        $proxyClass = $proxy->generateByTypes(ProxyWithHelper::class, ['content' => [Address::class]]);
        $filename = sys_get_temp_dir() . '/api_docs_proxy_test/' . str_replace('\\', '_', $proxyClass) . '.dto.proxy.php';

        $this->assertFileExists($filename);
        $content = file_get_contents($filename);
        $this->assertSame(1, substr_count($content, 'class ProxyWithHelperArrayAddress'));
        $this->assertSame(1, substr_count($content, 'class ProxyHelper'));
    }

    public function testGeneratedProxyRemovesIncompatibleDefaults(): void
    {
        (new ApiVariable())->collectProperty(ProxyNullableDefaults::class, 'data');
        (new ApiVariable())->collectProperty(ProxyNullableDefaults::class, 'content');
        $proxy = $this->makeGenerateProxyClass();
        $proxyClass = $proxy->generateByTypes(ProxyNullableDefaults::class, [
            'data' => PhpType::INT,
            'content' => null,
        ]);
        $filename = sys_get_temp_dir() . '/api_docs_proxy_test/' . str_replace('\\', '_', $proxyClass) . '.dto.proxy.php';

        $content = file_get_contents($filename);
        $this->assertStringContainsString('public int $data;', $content);
        $this->assertStringNotContainsString('public int $data = null', $content);

        require $filename;
        $this->assertTrue(class_exists($proxyClass, false));
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
