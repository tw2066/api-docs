<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Swagger\SwaggerJson;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Reflection\ClassInvoker;
use HyperfTest\ApiDocs\Controller\DemoController;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\User;
use HyperfTest\ApiDocs\Response\Activity;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class SwaggerCommonTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }

    public function testScan()
    {

    }
}
