<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
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

    public function testGetSimpleClassName()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);
        $container->shouldReceive('get')->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());
        $swaggerCommon = new SwaggerCommon();
        $simpleClassName = $swaggerCommon->getSimpleClassName('Hyperf\ApiDocs\Address');
        $this->assertEquals($simpleClassName, 'Address');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\Address_1');
        $this->assertEquals($simpleClassName, 'Address_1');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\Address');
        $this->assertEquals($simpleClassName, 'Address_2');
        $simpleClassName = $swaggerCommon->getSimpleClassName('Address');
        $this->assertEquals($simpleClassName, 'Address_3');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\Hyperf\ApiDocs\Address');
        $this->assertEquals($simpleClassName, 'Address');

        $simpleClassName = $swaggerCommon->getSimpleClassName('Hyperf\ApiDocs\City');
        $this->assertEquals($simpleClassName, 'City');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City');
        $this->assertEquals($simpleClassName, 'City_1');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City_1');
        $this->assertEquals($simpleClassName, 'City_1_1');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City_1_1');
        $this->assertEquals($simpleClassName, 'City_1_1_1');

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\DTO\City1');
        $this->assertEquals($simpleClassName, 'City1');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City');
        $this->assertEquals($simpleClassName, 'City_1');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\City');
        $this->assertEquals($simpleClassName, 'City_2');

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\DTO\City_2');
        $this->assertEquals($simpleClassName, 'City_2_1');

        $simpleClassName = $swaggerCommon->getSimpleClassName('int');
        $this->assertEquals($simpleClassName, 'Int');
        $simpleClassName = $swaggerCommon->getSimpleClassName('Int');
        $this->assertEquals($simpleClassName, 'Int_1');
        $simpleClassName = $swaggerCommon->getSimpleClassName('\Int');
        $this->assertEquals($simpleClassName, 'Int_1');

        $swaggerCommon->simpleClassNameClear();
    }
}
