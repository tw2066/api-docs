<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerJson;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Reflection\ClassInvoker;
use Hyperf\XxlJob\Annotation\XxlJob;
use Hyperf\XxlJob\Config;
use Hyperf\XxlJob\Dispatcher\XxlJobRoute;
use Hyperf\XxlJob\JobHandlerManager;
use Hyperf\XxlJob\Listener\BootAppRouteListener;
use HyperfTest\ApiDocs\Controller\DemoController;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\User;
use HyperfTest\ApiDocs\Response\Activity;
use HyperfTest\XxlJob\BarJobClass;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class SwaggerJsonTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }

    public function testScan()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);


        $config = m::mock(ConfigInterface::class);

        $config->shouldReceive('get')->with('apidocs.swagger')->andReturn([]);
        $config->shouldReceive('get')->with('apidocs.security_api_key', [])->andReturn([]);
        $config->shouldReceive('get')->with('apidocs')->andReturn(
            [
                // enable false 将不会启动 swagger 服务
                'enable' => true,
                'output_dir' => BASE_PATH . '/runtime/swagger',
                //认证api key
                'security_api_key' => ['Authorization'],
                //全局responses
                'responses' => [
                    401 => ['description' => 'Unauthorized'],
                ],
                // swagger 的基础配置
                'swagger' => [
                    'swagger' => '2.0',
                    'info' => [
                        'description' => 'swagger api desc',
                        'version' => '1.0.0',
                        'title' => 'API DOC',
                    ],
                    'host' => '',
                    'schemes' => [],
                ],
            ]
        );

        $container->shouldReceive('get')->with(ConfigInterface::class)->andReturn($config);

        $container->shouldReceive('get')->with(StdoutLoggerInterface::class)->andReturn(m::mock(StdoutLoggerInterface::class));
        $container->shouldReceive('get')->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());
        $container->shouldReceive('get')->with(DemoBodyRequest::class)->andReturn(m::mock(DemoBodyRequest::class));
        $container->shouldReceive('get')->with(Address::class)->andReturn(m::mock(Address::class));
        $container->shouldReceive('get')->with(User::class)->andReturn(m::mock(User::class));
        $container->shouldReceive('get')->with(Activity::class)->andReturn(m::mock(Activity::class));

        ApplicationContext::setContainer($container);

        $this->scan($container);

        $swaggerJson = new SwaggerJson('http');


        /** @var SwaggerJson $swaggerJson */
        $swaggerJson = new ClassInvoker($swaggerJson);
        $swaggerJson->addPath(DemoController::class, 'add', '/add', 'POST');

        $swagger = SwaggerJson::$swagger;
        $this->assertTrue(isset($swagger['paths']['/add']));
        $this->assertTrue(isset($swagger['paths']['/add']['post']));
        $this->assertSame('添加方法', $swagger['paths']['/add']['post']['summary']);
        $this->assertTrue(isset($swagger['paths']['/add']['post']['parameters']));
        $this->assertTrue(isset($swagger['definitions']));


        $this->assertSame('object', $swagger['definitions']['User']['type']);
        $this->assertSame('string', $swagger['definitions']['User']['properties']['name']['type']);
        $this->assertSame('名称', $swagger['definitions']['User']['properties']['name']['description']);
        $this->assertSame('integer', $swagger['definitions']['User']['properties']['age']['type']);
        $this->assertSame('年龄', $swagger['definitions']['User']['properties']['age']['description']);
    }

    private function scan($container)
    {
        $scanAnnotation = new ScanAnnotation($container);
        /** @var ScanAnnotation $scanAnnotation */
        $scanAnnotation = new ClassInvoker($scanAnnotation);
        $scanAnnotation->scan(DemoController::class, 'add');

    }
}
