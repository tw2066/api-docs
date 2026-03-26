<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\User;
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

    public function testSchemas(): void
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(true);
        $classname = DemoBodyRequest::class;
        $container->shouldReceive('get')->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);
        $schemas = $swaggerComponents->generateSchemas($classname);
        $properties = $schemas->properties;

        //dump($properties);die();

        $this->assertEquals('int', $properties[0]->property);
        $this->assertEquals('integer', $properties[0]->type);
        $this->assertEquals('12345', $properties[0]->default);

        $this->assertEquals('str', $properties[1]->property);
        $this->assertEquals('string', $properties[1]->type);
        $this->assertEquals('hi', $properties[1]->default);

        $this->assertEquals('bo', $properties[2]->property);
        $this->assertEquals('boolean', $properties[2]->type);

        $this->assertEquals('address', $properties[3]->property);
        $this->assertEquals('#/components/schemas/Address', $properties[3]->ref);

        $this->assertEquals('addressList1', $properties[4]->property);
        $this->assertEquals('array', $properties[4]->type);
        $this->assertEquals('#/components/schemas/Address', $properties[4]->items->ref);

        $this->assertEquals('addressList2', $properties[5]->property);
        $this->assertEquals('array', $properties[5]->type);
        $this->assertEquals('#/components/schemas/Address', $properties[5]->items->ref);

        $this->assertEquals('addressList3', $properties[6]->property);
        $this->assertEquals('array', $properties[6]->type);
        $this->assertEquals('#/components/schemas/Address', $properties[6]->items->ref);

        $this->assertEquals('intList1', $properties[7]->property);
        $this->assertEquals('array', $properties[7]->type);
        $this->assertEquals('integer', $properties[7]->items->type);

        $this->assertEquals('intList2', $properties[8]->property);
        $this->assertEquals('array', $properties[8]->type);
        $this->assertEquals('integer', $properties[8]->items->type);

        $addressSchemas = $swaggerComponents->generateSchemas(Address::class);

        $addressProperties = $addressSchemas->properties;

        $this->assertEquals('name', $addressProperties[0]->property);
        $this->assertEquals('string', $addressProperties[0]->type);

        $this->assertEquals('user', $addressProperties[1]->property);
        $this->assertEquals('#/components/schemas/User', $addressProperties[1]->ref);

        $swaggerCommon->simpleClassNameClear();
    }

    public function testGetPropertiesWithEmptyClassName(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);
        $result = $swaggerComponents->getProperties('');
        $this->assertEquals(['propertyArr' => [], 'requiredArr' => []], $result);
    }

    public function testSchemasCaching(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schema1 = $swaggerComponents->generateSchemas(User::class);
        $schema2 = $swaggerComponents->generateSchemas(User::class);

        $this->assertSame($schema1, $schema2);
    }

    public function testSchemasInstanceIsolation(): void
    {
        $swaggerCommon1 = new SwaggerCommon();
        $swaggerComponents1 = new SwaggerComponents($swaggerCommon1, new PropertyManager($swaggerCommon1, new PropertyEnum()), null);

        $swaggerCommon2 = new SwaggerCommon();
        $swaggerComponents2 = new SwaggerComponents($swaggerCommon2, new PropertyManager($swaggerCommon2, new PropertyEnum()), null);

        $schema1 = $swaggerComponents1->generateSchemas(User::class);
        $schema2 = $swaggerComponents2->generateSchemas(User::class);

        $this->assertNotSame($schema1, $schema2);
        $this->assertEquals($schema1->schema, $schema2->schema);
    }

    public function testGetAndSetSchemas(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schemas = $swaggerComponents->getSchemas();
        $this->assertIsArray($schemas);

        $swaggerComponents->setSchemas(['TestSchema' => 'value']);
        $this->assertEquals(['TestSchema' => 'value'], $swaggerComponents->getSchemas());
    }

    public function testUserSchemaProperties(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schemas = $swaggerComponents->generateSchemas(User::class);
        $properties = $schemas->properties;

        $propertyNames = array_map(fn ($p) => $p->property, $properties);
        $this->assertContains('name', $propertyNames);
        $this->assertContains('age', $propertyNames);
    }
}
