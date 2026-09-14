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
use ReflectionClass;

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

    public function testGetSimpleClassName(): void
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);
        $container->shouldReceive('get')->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());
        $swaggerCommon = new SwaggerCommon();

        $simpleClassName = $swaggerCommon->getSimpleClassName('Hyperf\ApiDocs\Address');
        $this->assertEquals('Address', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\Address_1');
        $this->assertEquals('Address_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\Address');
        $this->assertEquals('Address_2', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('Address');
        $this->assertEquals('Address_3', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\Hyperf\ApiDocs\Address');
        $this->assertEquals('Address', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('Hyperf\ApiDocs\City');
        $this->assertEquals('City', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City');
        $this->assertEquals('City_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City_1');
        $this->assertEquals('City_1_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City_1_1');
        $this->assertEquals('City_1_1_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\DTO\City1');
        $this->assertEquals('City1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\City');
        $this->assertEquals('City_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\City');
        $this->assertEquals('City_2', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\ApiDocs\DTO\City_2');
        $this->assertEquals('City_2_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('int');
        $this->assertEquals('Int', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('Int');
        $this->assertEquals('Int_1', $simpleClassName);

        $simpleClassName = $swaggerCommon->getSimpleClassName('\Int');
        $this->assertEquals('Int_1', $simpleClassName);

        $swaggerCommon->simpleClassNameClear();
    }

    public function testGetSimpleClassNameWithEmptyValue(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $simpleClassName = $swaggerCommon->getSimpleClassName('');
        $this->assertEquals('Null', $simpleClassName);
    }

    public function testGetSimpleClassNameWithNullValue(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $simpleClassName = $swaggerCommon->getSimpleClassName(null);
        $this->assertEquals('Null', $simpleClassName);
    }

    public function testGetSwaggerType(): void
    {
        $swaggerCommon = new SwaggerCommon();

        $this->assertEquals('integer', $swaggerCommon->getSwaggerType('int'));
        $this->assertEquals('integer', $swaggerCommon->getSwaggerType('integer'));
        $this->assertEquals('boolean', $swaggerCommon->getSwaggerType('boolean'));
        $this->assertEquals('boolean', $swaggerCommon->getSwaggerType('bool'));
        $this->assertEquals('number', $swaggerCommon->getSwaggerType('double'));
        $this->assertEquals('number', $swaggerCommon->getSwaggerType('float'));
        $this->assertEquals('number', $swaggerCommon->getSwaggerType('number'));
        $this->assertEquals('array', $swaggerCommon->getSwaggerType('array'));
        $this->assertEquals('object', $swaggerCommon->getSwaggerType('object'));
        $this->assertEquals('string', $swaggerCommon->getSwaggerType('string'));
        $this->assertEquals('null', $swaggerCommon->getSwaggerType('unknown'));
    }

    public function testGetSimpleType2SwaggerType(): void
    {
        $swaggerCommon = new SwaggerCommon();

        $this->assertEquals('integer', $swaggerCommon->getSimpleType2SwaggerType('int'));
        $this->assertEquals('integer', $swaggerCommon->getSimpleType2SwaggerType('integer'));
        $this->assertEquals('boolean', $swaggerCommon->getSimpleType2SwaggerType('boolean'));
        $this->assertEquals('boolean', $swaggerCommon->getSimpleType2SwaggerType('bool'));
        $this->assertEquals('number', $swaggerCommon->getSimpleType2SwaggerType('double'));
        $this->assertEquals('number', $swaggerCommon->getSimpleType2SwaggerType('float'));
        $this->assertEquals('string', $swaggerCommon->getSimpleType2SwaggerType('string'));
        $this->assertEquals('string', $swaggerCommon->getSimpleType2SwaggerType('mixed'));
        $this->assertNull($swaggerCommon->getSimpleType2SwaggerType('array'));
        $this->assertNull($swaggerCommon->getSimpleType2SwaggerType('object'));
    }

    public function testGetComponentsName(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->getSimpleClassName('Hyperf\ApiDocs\Request\User');
        $componentName = $swaggerCommon->getComponentsName('Hyperf\ApiDocs\Request\User');
        $this->assertEquals('#/components/schemas/User', $componentName);
    }

    public function testSimpleClassNameClear(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->getSimpleClassName('Test\Class\Name');
        $swaggerCommon->simpleClassNameClear();

        $reflection = new ReflectionClass($swaggerCommon);
        $property = $reflection->getProperty('classNameCache');
        $property->setAccessible(true);
        $this->assertEmpty($property->getValue($swaggerCommon));
    }

    public function testInstanceIsolation(): void
    {
        $swaggerCommon1 = new SwaggerCommon();
        $swaggerCommon2 = new SwaggerCommon();

        $swaggerCommon1->getSimpleClassName('Hyperf\ApiDocs\TestClass');
        $this->assertEquals('TestClass', $swaggerCommon1->getSimpleClassName('Hyperf\ApiDocs\TestClass'));

        $this->assertEquals('TestClass', $swaggerCommon2->getSimpleClassName('Hyperf\ApiDocs\TestClass'));

        $swaggerCommon1->simpleClassNameClear();

        $this->assertEquals('TestClass', $swaggerCommon1->getSimpleClassName('Hyperf\ApiDocs\TestClass'));
        $this->assertEquals('TestClass', $swaggerCommon2->getSimpleClassName('Hyperf\ApiDocs\TestClass'));
    }
}
