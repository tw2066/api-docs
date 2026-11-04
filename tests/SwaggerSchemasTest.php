<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Exception\DtoException;
use Hyperf\DTO\JsonMapper;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use HyperfTest\ApiDocs\Request\AbstractPayment;
use HyperfTest\ApiDocs\Request\AbstractSchema;
use HyperfTest\ApiDocs\Request\AccessorSchema;
use HyperfTest\ApiDocs\Request\Address;
use HyperfTest\ApiDocs\Request\AliasRequired;
use HyperfTest\ApiDocs\Request\CardPayment;
use HyperfTest\ApiDocs\Request\DemoBodyRequest;
use HyperfTest\ApiDocs\Request\EnumSchema;
use HyperfTest\ApiDocs\Request\InputAccessibility;
use HyperfTest\ApiDocs\Request\InterfaceSchema;
use HyperfTest\ApiDocs\Request\OtherSchemaPayment;
use HyperfTest\ApiDocs\Request\PrivateAlias;
use HyperfTest\ApiDocs\Request\PrivateRefAlias;
use HyperfTest\ApiDocs\Request\SchemaPayment;
use HyperfTest\ApiDocs\Request\SchemaPaymentInterface;
use HyperfTest\ApiDocs\Request\StatusEnum;
use HyperfTest\ApiDocs\Request\StatusInterface;
use HyperfTest\ApiDocs\Request\TreeNode;
use HyperfTest\ApiDocs\Request\User;
use Mockery as m;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
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

        // dump($properties);die();

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

        // 数组属性不写入 default，避免 Knife4j 把默认值序列化成字符串示例
        $this->assertEquals('addressList4', $properties[9]->property);
        $this->assertEquals('array', $properties[9]->type);
        $this->assertSame(Generator::UNDEFINED, $properties[9]->default);

        $addressSchemas = $swaggerComponents->generateSchemas(Address::class);

        $addressProperties = $addressSchemas->properties;

        $this->assertEquals('name', $addressProperties[0]->property);
        $this->assertEquals('string', $addressProperties[0]->type);

        $this->assertEquals('user', $addressProperties[1]->property);
        $this->assertEquals('#/components/schemas/User', $addressProperties[1]->ref);

        $swaggerCommon->simpleClassNameClear();
    }

    /**
     * 私有别名属性仍可用于响应，但 DTO 4.0 请求输入为只读.
     */
    public function testPrivateJsonFieldAliasWithoutSetterIsReadOnly(): void
    {
        (new JSONField('name_arr'))->collectProperty(PrivateAlias::class, 'nameArr');
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schema = $swaggerComponents->generateSchemas(PrivateAlias::class);

        $this->assertCount(1, $schema->properties);
        $this->assertSame('name_arr', $schema->properties[0]->property);
        $this->assertTrue($schema->properties[0]->readOnly);
    }

    public function testInterfacePropertiesUseObjectSchemaType(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schema = $swaggerComponents->generateSchemas(InterfaceSchema::class);
        $properties = $this->propertiesByName($schema);

        $this->assertSame('object', $properties['payment']->type);
        $this->assertSame('array', $properties['payments']->type);
        $this->assertSame('object', $properties['payments']->items->type);
        $this->assertSame(Generator::UNDEFINED, $properties['untyped']->items->type);
        $this->assertSame('array', $properties['paymentMatrix']->items->type);
        $this->assertSame('object', $properties['paymentMatrix']->items->items->type);
        $this->assertSame('array', $properties['intMatrix']->items->type);
        $this->assertSame('integer', $properties['intMatrix']->items->items->type);
    }

    public function testInterfacePropertiesUseStaticClassMapSchema(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = SchemaPayment::class;
        $swaggerComponents = $this->createComponents($mapper);

        $schema = $swaggerComponents->generateSchemas(InterfaceSchema::class);
        $properties = $this->propertiesByName($schema);

        $this->assertSame('#/components/schemas/SchemaPayment', $properties['payment']->ref);
        $this->assertSame('#/components/schemas/SchemaPayment', $properties['payments']->items->ref);
        $this->assertSame('#/components/schemas/SchemaPayment', $properties['paymentMatrix']->items->items->ref);
        $this->assertArrayHasKey('SchemaPayment', $swaggerComponents->getSchemas());
    }

    public function testRequiredUsesJsonFieldAlias(): void
    {
        (new JSONField('display_name'))->collectProperty(AliasRequired::class, 'displayName');
        (new Required())->collectProperty(AliasRequired::class, 'displayName');
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schema = $swaggerComponents->generateSchemas(AliasRequired::class);

        $this->assertSame(['display_name'], $schema->required);
        $this->assertSame('display_name', $schema->properties[0]->property);
    }

    public function testReadonlyPropertyIsReadOnlyAndStaticPropertyIsSkipped(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schema = $swaggerComponents->generateSchemas(InputAccessibility::class);
        $properties = $this->propertiesByName($schema);

        $this->assertArrayNotHasKey('static', $properties);
        $this->assertTrue($properties['id']->readOnly);
        $this->assertSame(Generator::UNDEFINED, $properties['name']->readOnly);
    }

    public function testReadOnlyReferenceSurvivesJsonSerialization(): void
    {
        (new JSONField('the_address'))->collectProperty(PrivateRefAlias::class, 'address');
        $swaggerComponents = $this->createComponents();

        $schema = $swaggerComponents->generateSchemas(PrivateRefAlias::class);
        $data = json_decode(json_encode($schema), true, 512, JSON_THROW_ON_ERROR);
        $property = $data['properties']['the_address'];

        $this->assertTrue($property['readOnly']);
        $this->assertSame('#/components/schemas/Address', $property['allOf'][0]['$ref']);
        $this->assertArrayNotHasKey('$ref', $property);
    }

    public function testAccessorVisibilityRequiredAndComplexParameterTypes(): void
    {
        $this->collectAccessorAnnotations();
        $swaggerComponents = $this->createComponents();

        $schema = $swaggerComponents->generateSchemas(AccessorSchema::class);
        $properties = $this->propertiesByName($schema);

        $this->assertArrayNotHasKey('status', $properties);
        $this->assertArrayNotHasKey('internal', $properties);
        $this->assertSame(Generator::UNDEFINED, $properties['id']->readOnly);
        $this->assertSame(Generator::UNDEFINED, $properties['name_arr']->readOnly);
        $this->assertContains('name_arr', $schema->required);
        $this->assertSame('#/components/schemas/Address', $properties['tag']->ref);
        $this->assertSame('object', $properties['payment']->type);
        $this->assertSame(Generator::UNDEFINED, $properties['ids']->items->type);
        $this->assertSame(Generator::UNDEFINED, $properties['payload']->type);
    }

    public function testIgnoreVisibilityUsesDtoAccessorRules(): void
    {
        $this->collectAccessorAnnotations();
        $mapper = new JsonMapper();
        $mapper->bIgnoreVisibility = true;
        $swaggerComponents = $this->createComponents($mapper);

        $properties = $this->propertiesByName($swaggerComponents->generateSchemas(AccessorSchema::class));

        $this->assertArrayHasKey('status', $properties);
        $this->assertArrayHasKey('internal', $properties);
        $this->assertSame(Generator::UNDEFINED, $properties['status']->readOnly);
        $this->assertSame(Generator::UNDEFINED, $properties['internal']->readOnly);
    }

    public function testAbstractClassUsesClassMapSchema(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[AbstractPayment::class] = CardPayment::class;
        $swaggerComponents = $this->createComponents($mapper);

        $properties = $this->propertiesByName($swaggerComponents->generateSchemas(AbstractSchema::class));

        $this->assertSame('#/components/schemas/CardPayment', $properties['payment']->ref);
        $this->assertArrayHasKey('CardPayment', $swaggerComponents->getSchemas());
    }

    public function testClassMapEnumTargetUsesScalarEnumSchema(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[StatusInterface::class] = StatusEnum::class;
        $swaggerComponents = $this->createComponents($mapper);

        $properties = $this->propertiesByName($swaggerComponents->generateSchemas(EnumSchema::class));

        $this->assertSame('string', $properties['status']->type);
        $this->assertSame(['active', 'disabled'], $properties['status']->enum);
        $this->assertArrayNotHasKey('StatusEnum', $swaggerComponents->getSchemas());
    }

    public function testClassMapLeadingSlashKeyTakesPriority(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = SchemaPayment::class;
        $mapper->classMap['\\' . SchemaPaymentInterface::class] = OtherSchemaPayment::class;
        $swaggerComponents = $this->createComponents($mapper);

        $schema = $swaggerComponents->createSchema(type: SchemaPaymentInterface::class);

        $this->assertSame('#/components/schemas/OtherSchemaPayment', $schema->ref);
    }

    public function testCallableClassMapUsesDtoResolver(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = static fn ($type, $value) => SchemaPayment::class;
        $swaggerComponents = $this->createComponents($mapper);

        $schema = $swaggerComponents->createSchema(type: SchemaPaymentInterface::class);

        $this->assertSame('#/components/schemas/SchemaPayment', $schema->ref);
    }

    public function testInvalidClassMapTargetThrows(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = 'Missing\PaymentClass';
        $swaggerComponents = $this->createComponents($mapper);

        $this->expectException(DtoException::class);
        $swaggerComponents->createSchema(type: SchemaPaymentInterface::class);
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

    /**
     * 自引用与互相引用的 DTO 不会导致 generateSchemas 无限递归.
     */
    public function testCircularReferenceSchemas(): void
    {
        $swaggerCommon = new SwaggerCommon();
        $swaggerComponents = new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);

        $schema = $swaggerComponents->generateSchemas(TreeNode::class);
        $properties = $schema->properties;

        $propertyMap = [];
        foreach ($properties as $property) {
            $propertyMap[$property->property] = $property;
        }
        // 自引用
        $this->assertSame('#/components/schemas/TreeNode', $propertyMap['child']->ref);
        // 互相引用 A → B，B 的 schema 也应生成且 B → A 正常回指
        $this->assertSame('#/components/schemas/TreeSibling', $propertyMap['sibling']->ref);
        $schemas = $swaggerComponents->getSchemas();
        $this->assertArrayHasKey('TreeNode', $schemas);
        $this->assertArrayHasKey('TreeSibling', $schemas);
        $siblingProperties = $schemas['TreeSibling']->properties;
        $this->assertSame('#/components/schemas/TreeNode', $siblingProperties[1]->ref);

        $swaggerCommon->simpleClassNameClear();
    }

    private function createComponents(?JsonMapper $mapper = null): SwaggerComponents
    {
        $swaggerCommon = new SwaggerCommon();
        if ($mapper !== null) {
            $swaggerCommon->setDtoMapper($mapper);
        }
        return new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null);
    }

    private function collectAccessorAnnotations(): void
    {
        (new JSONField('name_arr'))->collectProperty(AccessorSchema::class, 'nameArr');
        (new Required())->collectProperty(AccessorSchema::class, 'nameArr');
    }

    private function propertiesByName(Schema $schema): array
    {
        $properties = [];
        foreach ($schema->properties as $property) {
            $properties[$property->property] = $property;
        }
        return $properties;
    }
}
