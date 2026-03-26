<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerOpenApi;
use Hyperf\ApiDocs\Swagger\SwaggerPaths;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 * @coversNothing
 */
class SwaggerPathsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }

    public function testOperationIdGeneration(): void
    {
        $container = m::mock(ContainerInterface::class);
        $config = m::mock(ConfigInterface::class);
        $logger = m::mock(StdoutLoggerInterface::class);
        $swaggerOpenApi = m::mock(SwaggerOpenApi::class);
        $swaggerCommon = new SwaggerCommon();

        $swaggerPaths = new SwaggerPaths(
            'http',
            $config,
            $logger,
            $swaggerOpenApi,
            $swaggerCommon
        );

        $reflection = new ReflectionClass($swaggerPaths);
        $property = $reflection->getProperty('operationIds');

        $this->assertIsArray($property->getValue($swaggerPaths));
    }

    public function testOperationIdInstanceIsolation(): void
    {
        $container = m::mock(ContainerInterface::class);
        $config = m::mock(ConfigInterface::class);
        $logger = m::mock(StdoutLoggerInterface::class);
        $swaggerOpenApi = m::mock(SwaggerOpenApi::class);
        $swaggerCommon = new SwaggerCommon();

        $swaggerPaths1 = new SwaggerPaths(
            'http',
            $config,
            $logger,
            $swaggerOpenApi,
            $swaggerCommon
        );

        $swaggerPaths2 = new SwaggerPaths(
            'http2',
            $config,
            $logger,
            $swaggerOpenApi,
            $swaggerCommon
        );

        $reflection1 = new ReflectionClass($swaggerPaths1);
        $property1 = $reflection1->getProperty('operationIds');
        $property1->setAccessible(true);

        $reflection2 = new ReflectionClass($swaggerPaths2);
        $property2 = $reflection2->getProperty('operationIds');
        $property2->setAccessible(true);

        $property1->setValue($swaggerPaths1, ['test' => true]);

        $this->assertArrayNotHasKey('test', $property2->getValue($swaggerPaths2));
    }

    public function testGetClassMethodPath(): void
    {
        $container = m::mock(ContainerInterface::class);
        $config = m::mock(ConfigInterface::class);
        $logger = m::mock(StdoutLoggerInterface::class);
        $swaggerOpenApi = m::mock(SwaggerOpenApi::class);
        $swaggerCommon = new SwaggerCommon();

        $swaggerPaths = new SwaggerPaths(
            'http',
            $config,
            $logger,
            $swaggerOpenApi,
            $swaggerCommon
        );

        $method = new ReflectionMethod($swaggerPaths, 'getClassMethodPath');
        $method->setAccessible(true);

        $result = $method->invoke($swaggerPaths, 'Hyperf\ApiDocs\Controller\UserController', 'getUser');

        $this->assertStringContainsString('H.A.C.UserController', $result);
        $this->assertStringContainsString('getUser', $result);
    }

    public function testGetClassMethodPathWithShortNamespace(): void
    {
        $container = m::mock(ContainerInterface::class);
        $config = m::mock(ConfigInterface::class);
        $logger = m::mock(StdoutLoggerInterface::class);
        $swaggerOpenApi = m::mock(SwaggerOpenApi::class);
        $swaggerCommon = new SwaggerCommon();

        $swaggerPaths = new SwaggerPaths(
            'http',
            $config,
            $logger,
            $swaggerOpenApi,
            $swaggerCommon
        );

        $method = new ReflectionMethod($swaggerPaths, 'getClassMethodPath');
        $method->setAccessible(true);

        $result = $method->invoke($swaggerPaths, 'Controller\UserController', 'index');

        $this->assertStringContainsString('Controller', $result);
        $this->assertStringContainsString('index', $result);
    }
}
