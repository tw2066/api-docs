<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\DtoCommon;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\DTO\Scan\Scan;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class SwaggerSchemasTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }

    public function testSchemas()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);
        $classname = DemoBodyRequest::class;
        // dto
        $container->shouldReceive('get')->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());
        $swaggerCommon = new SwaggerCommon();

        $swaggerComponents = new SwaggerComponents($swaggerCommon,new PropertyManager($swaggerCommon,new PropertyEnum()));
        $schemas = $swaggerComponents->generateSchemas($classname);
        $properties = $schemas->properties;
        $this->assertEquals($properties[0]->property, 'int');
        $this->assertEquals($properties[0]->type, 'integer');
        $this->assertEquals($properties[0]->default, '12345');

        $this->assertEquals($properties[1]->property, 'str');
        $this->assertEquals($properties[1]->type, 'string');
        $this->assertEquals($properties[1]->default, 'hi');

        $this->assertEquals($properties[2]->property, 'bo');
        $this->assertEquals($properties[2]->type, 'boolean');
        $this->assertEquals($properties[2]->default, true);

        $this->assertEquals($properties[3]->property, 'address');
        $this->assertEquals($properties[3]->ref, '#/components/schemas/Address');
        $this->assertEquals($properties[3]->default, true);

        $this->assertEquals($properties[4]->property, 'addressList1');
        $this->assertEquals($properties[4]->type, 'array');
        $this->assertEquals($properties[4]->items->ref, '#/components/schemas/Address');

        $this->assertEquals($properties[5]->property, 'addressList2');
        $this->assertEquals($properties[5]->type, 'array');
        $this->assertEquals($properties[5]->items->ref, '#/components/schemas/Address');

        $this->assertEquals($properties[6]->property, 'addressList3');
        $this->assertEquals($properties[6]->type, 'array');
        $this->assertEquals($properties[6]->items->ref, '#/components/schemas/Address');

        $this->assertEquals($properties[7]->property, 'intList1');
        $this->assertEquals($properties[7]->type, 'array');
        $this->assertEquals($properties[7]->items->type, 'integer');

        $this->assertEquals($properties[8]->property, 'intList2');
        $this->assertEquals($properties[8]->type, 'array');
        $this->assertEquals($properties[8]->items->type, 'integer');

        $addressSchemas = $swaggerComponents->generateSchemas(Address::class);

        // address class
        $addressProperties = $addressSchemas->properties;

        $this->assertEquals($addressProperties[0]->property, 'name');
        $this->assertEquals($addressProperties[0]->type, 'string');

        $this->assertEquals($addressProperties[1]->property, 'user');
        $this->assertEquals($addressProperties[1]->ref, '#/components/schemas/User');
    }
}
