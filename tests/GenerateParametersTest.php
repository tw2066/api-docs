<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Swagger\GenerateParameters;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\JsonMapper;
use Hyperf\DTO\Scan\MethodParameter;
use Hyperf\DTO\Scan\MethodParametersManager;
use Hyperf\DTO\Scan\PropertyEnum;
use Hyperf\DTO\Scan\PropertyManager;
use HyperfTest\ApiDocs\Request\AccessorSchema;
use HyperfTest\ApiDocs\Request\AliasRequired;
use HyperfTest\ApiDocs\Request\InputAccessibility;
use HyperfTest\ApiDocs\Request\PrivateAlias;
use HyperfTest\ApiDocs\Request\SchemaPayment;
use HyperfTest\ApiDocs\Request\SchemaPaymentInterface;
use Mockery as m;
use OpenApi\Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class GenerateParametersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }

    /**
     * 路由占位符中的简单类型参数应生成为 path 必填.
     */
    public function testPathParamInRoute(): void
    {
        $result = $this->generate('/user/{id}', [
            $this->reflectionType('int', 'id'),
        ]);

        $this->assertCount(1, $result['parameter']);
        $this->assertSame('path', $result['parameter'][0]->in);
        $this->assertTrue($result['parameter'][0]->required);
        $this->assertSame('id', $result['parameter'][0]->name);
    }

    /**
     * 带正则约束的占位符 {id:\d+} 也应识别为 path 参数.
     */
    public function testPathParamWithRegexConstraint(): void
    {
        $result = $this->generate('/user/{id:\d+}', [
            $this->reflectionType('int', 'id'),
        ]);

        $this->assertSame('path', $result['parameter'][0]->in);
        $this->assertTrue($result['parameter'][0]->required);
    }

    /**
     * DTO 4.0 的私有别名属性没有业务 setter 时不可写，不应生成请求参数.
     */
    public function testPrivateJsonFieldAliasWithoutSetterIsNotParameter(): void
    {
        (new JSONField('name_arr'))->collectProperty(PrivateAlias::class, 'nameArr');
        $parameters = $this->createGenerateParameters()->getParameterArrByClass(PrivateAlias::class, 'query');

        $this->assertCount(0, $parameters);
    }

    public function testPublicJsonFieldAliasIsParameter(): void
    {
        (new JSONField('display_name'))->collectProperty(AliasRequired::class, 'displayName');
        (new Required())->collectProperty(AliasRequired::class, 'displayName');
        $parameters = $this->createGenerateParameters()->getParameterArrByClass(AliasRequired::class, 'query');

        $this->assertCount(1, $parameters);
        $this->assertSame('display_name', $parameters[0]->name);
        $this->assertTrue($parameters[0]->required);
    }

    public function testReadonlyAndStaticPropertiesAreNotParameters(): void
    {
        $parameters = $this->createGenerateParameters()->getParameterArrByClass(InputAccessibility::class, 'query');

        $this->assertCount(1, $parameters);
        $this->assertSame('name', $parameters[0]->name);
    }

    public function testAccessorAndComplexQuerySchemas(): void
    {
        (new JSONField('name_arr'))->collectProperty(AccessorSchema::class, 'nameArr');
        (new Required())->collectProperty(AccessorSchema::class, 'nameArr');
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = SchemaPayment::class;
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->setDtoMapper($mapper);
        $parameters = $this->createGenerateParameters(swaggerCommon: $swaggerCommon)->getParameterArrByClass(AccessorSchema::class, 'query');
        $parameters = $this->parametersByName($parameters);

        $this->assertArrayNotHasKey('status', $parameters);
        $this->assertArrayNotHasKey('internal', $parameters);
        $this->assertArrayHasKey('id', $parameters);
        $this->assertTrue($parameters['name_arr']->required);
        $this->assertSame('#/components/schemas/Address', $parameters['tag']->schema->ref);
        $this->assertSame('#/components/schemas/SchemaPayment', $parameters['payment']->schema->ref);
        $this->assertSame(Generator::UNDEFINED, $parameters['ids']->schema->items->type);
        $this->assertSame(Generator::UNDEFINED, $parameters['payload']->schema->type);
    }

    public function testIgnoreVisibilityAllowsPrivateParameters(): void
    {
        $mapper = new JsonMapper();
        $mapper->bIgnoreVisibility = true;
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->setDtoMapper($mapper);

        $parameters = $this->createGenerateParameters(swaggerCommon: $swaggerCommon)
            ->getParameterArrByClass(AccessorSchema::class, 'query');
        $parameters = $this->parametersByName($parameters);

        $this->assertArrayHasKey('status', $parameters);
        $this->assertArrayHasKey('internal', $parameters);
    }

    public function testInterfaceMethodParameterUsesClassMapForRequestBody(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = SchemaPayment::class;
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->setDtoMapper($mapper);
        $methodParameter = (new MethodParameter())->setIsRequestBody(true);
        $manager = m::mock(MethodParametersManager::class);
        $manager->shouldReceive('getMethodParameter')->with('DemoController', 'list', 'payment')->andReturn($methodParameter);

        $result = $this->createGenerateParameters('/', [
            $this->reflectionType(SchemaPaymentInterface::class, 'payment'),
        ], $swaggerCommon, $manager)->generate();

        $this->assertSame('#/components/schemas/SchemaPayment', $result['requestBody']->content[0]->schema->ref);
    }

    public function testInterfaceMethodParameterUsesClassMapForQuery(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = SchemaPayment::class;
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->setDtoMapper($mapper);
        $methodParameter = (new MethodParameter())->setIsRequestQuery(true);
        $manager = m::mock(MethodParametersManager::class);
        $manager->shouldReceive('getMethodParameter')->with('DemoController', 'list', 'payment')->andReturn($methodParameter);

        $result = $this->createGenerateParameters('/', [
            $this->reflectionType(SchemaPaymentInterface::class, 'payment'),
        ], $swaggerCommon, $manager)->generate();

        $this->assertCount(1, $result['parameter']);
        $this->assertSame('provider', $result['parameter'][0]->name);
    }

    public function testRequestBodyArrayHasItemsWithoutNullComponent(): void
    {
        $generateParameters = $this->createGenerateParameters();
        $method = new \ReflectionMethod($generateParameters, 'getJsonContent');
        $method->setAccessible(true);

        $schema = $method->invoke($generateParameters, 'array');
        $property = new \ReflectionProperty($generateParameters, 'swaggerComponents');
        $property->setAccessible(true);
        $components = $property->getValue($generateParameters);

        $this->assertSame('array', $schema->type);
        $this->assertSame(Generator::UNDEFINED, $schema->items->type);
        $this->assertArrayNotHasKey('Null', $components->getSchemas());
    }

    public function testRequestBodyInterfaceMatrixUsesClassMapAndArrayDepth(): void
    {
        $mapper = new JsonMapper();
        $mapper->classMap[SchemaPaymentInterface::class] = SchemaPayment::class;
        $swaggerCommon = new SwaggerCommon();
        $swaggerCommon->setDtoMapper($mapper);
        $generateParameters = $this->createGenerateParameters(swaggerCommon: $swaggerCommon);
        $property = (new PropertyManager($swaggerCommon, new PropertyEnum()))
            ->createPropertyFromType(SchemaPaymentInterface::class . '[][]', '');
        $method = new \ReflectionMethod($generateParameters, 'getJsonContent');
        $method->setAccessible(true);

        $schema = $method->invoke($generateParameters, 'array', $property);

        $this->assertSame('array', $schema->type);
        $this->assertSame('array', $schema->items->type);
        $this->assertSame('#/components/schemas/SchemaPayment', $schema->items->items->ref);
    }

    private function reflectionType(string $type, string $name, bool $allowsNull = false, bool $defaultValueAvailable = false): ReflectionType
    {
        return new ReflectionType($type, $allowsNull, [
            'defaultValueAvailable' => $defaultValueAvailable,
            'defaultValue' => null,
            'name' => $name,
            'attributes' => [],
        ]);
    }

    private function createGenerateParameters(string $route = '/', array $definitions = [], ?SwaggerCommon $swaggerCommon = null, ?MethodParametersManager $methodParametersManager = null): GenerateParameters
    {
        $swaggerCommon ??= new SwaggerCommon();
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')->andReturn(false);
        $methodDefinitionCollector = m::mock(MethodDefinitionCollectorInterface::class);
        $methodDefinitionCollector->shouldReceive('getParameters')->andReturn($definitions);

        return new GenerateParameters(
            'DemoController',
            'list',
            [],
            [],
            $route,
            $container,
            $methodDefinitionCollector,
            new SwaggerComponents($swaggerCommon, new PropertyManager($swaggerCommon, new PropertyEnum()), null),
            $swaggerCommon,
            new PropertyManager($swaggerCommon, new PropertyEnum()),
            $methodParametersManager ?? m::mock(MethodParametersManager::class),
        );
    }

    private function parametersByName(array $parameters): array
    {
        $result = [];
        foreach ($parameters as $parameter) {
            $result[$parameter->name] = $parameter;
        }
        return $result;
    }

    private function generate(string $route, array $definitions): array
    {
        return $this->createGenerateParameters($route, $definitions)->generate();
    }
}
