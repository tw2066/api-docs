<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Attribute;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Annotation\ApiSecurity;
use Hyperf\ApiDocs\Annotation\ApiVariable;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\DTO\Type\PhpType;
use OpenApi\Generator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 * @coversNothing
 */
class AnnotationTest extends TestCase
{
    public function testApiAnnotation(): void
    {
        $api = new Api(['tag1', 'tag2'], 'Test description', 1, false);

        $this->assertEquals(['tag1', 'tag2'], $api->tags);
        $this->assertEquals('Test description', $api->description);
        $this->assertEquals(1, $api->position);
        $this->assertFalse($api->hidden);
    }

    public function testApiAnnotationWithHidden(): void
    {
        $api = new Api(tags: null, description: '', position: 0, hidden: true);

        $this->assertTrue($api->hidden);
    }

    public function testApiOperationAnnotation(): void
    {
        $apiOperation = new ApiOperation(
            summary: 'Test summary',
            description: 'Test description',
            hidden: false,
            security: true,
            deprecated: false
        );

        $this->assertEquals('Test summary', $apiOperation->summary);
        $this->assertEquals('Test description', $apiOperation->description);
        $this->assertFalse($apiOperation->hidden);
        $this->assertTrue($apiOperation->security);
        $this->assertFalse($apiOperation->deprecated);
    }

    public function testApiOperationWithHidden(): void
    {
        $apiOperation = new ApiOperation(hidden: true);

        $this->assertTrue($apiOperation->hidden);
    }

    public function testApiModelAnnotation(): void
    {
        $apiModel = new ApiModel('Test model description');

        $this->assertEquals('Test model description', $apiModel->value);
    }

    public function testApiModelPropertyAnnotation(): void
    {
        $apiModelProperty = new ApiModelProperty(
            value: 'Test property',
            example: 'example_value',
            hidden: false,
            required: true,
            simpleType: null
        );

        $this->assertEquals('Test property', $apiModelProperty->value);
        $this->assertEquals('example_value', $apiModelProperty->example);
        $this->assertFalse($apiModelProperty->hidden);
        $this->assertTrue($apiModelProperty->required);
    }

    public function testApiModelPropertyWithPhpTypeEnum(): void
    {
        $phpType = PhpType::STRING;
        $apiModelProperty = new ApiModelProperty(
            value: 'Test property',
            simpleType: $phpType
        );

        $this->assertEquals('string', $apiModelProperty->phpType);
    }

    public function testApiResponseAnnotation(): void
    {
        $apiResponse = new ApiResponse(
            returnType: null,
            response: '200',
            description: 'Success'
        );

        $this->assertEquals('200', $apiResponse->response);
        $this->assertEquals('Success', $apiResponse->description);
    }

    public function testApiResponseWithNullReturnType(): void
    {
        $apiResponse = new ApiResponse(
            returnType: null,
            response: '200',
            description: 'Success'
        );

        $this->assertNull($apiResponse->returnType);
    }

    public function testApiResponseWithEmptyArrayReturnType(): void
    {
        $apiResponse = new ApiResponse(
            returnType: [],
            response: '200',
            description: 'Success'
        );

        $this->assertEquals('array', $apiResponse->returnType);
    }

    public function testApiResponseThrowsExceptionForUnsupportedType(): void
    {
        $this->expectException(ApiDocsException::class);
        $this->expectExceptionMessage('ApiResponse: Unsupported data type');

        new ApiResponse(
            returnType: 'unsupported_string_type',
            response: '200',
            description: 'Success'
        );
    }

    public function testApiHeaderAnnotation(): void
    {
        $apiHeader = new ApiHeader(
            name: 'Authorization',
            required: true,
            type: 'string',
            default: Generator::UNDEFINED,
            description: 'Bearer token',
            format: Generator::UNDEFINED,
            hidden: false
        );

        $this->assertEquals('Authorization', $apiHeader->name);
        $this->assertTrue($apiHeader->required);
        $this->assertEquals('string', $apiHeader->type);
        $this->assertEquals('Bearer token', $apiHeader->description);
        $this->assertFalse($apiHeader->hidden);
        $this->assertEquals('header', $apiHeader->getIn());
    }

    public function testApiFormDataAnnotation(): void
    {
        $apiFormData = new ApiFormData(
            name: 'file',
            required: true,
            type: 'string',
            default: Generator::UNDEFINED,
            description: 'Upload file',
            format: Generator::UNDEFINED,
            hidden: false
        );

        $this->assertEquals('file', $apiFormData->name);
        $this->assertTrue($apiFormData->required);
        $this->assertEquals('formData', $apiFormData->getIn());
    }

    public function testApiSecurityAnnotation(): void
    {
        $apiSecurity = new ApiSecurity(
            name: 'BearerAuth',
            value: []
        );

        $this->assertEquals('BearerAuth', $apiSecurity->name);
        $this->assertEquals([], $apiSecurity->value);
    }

    public function testApiVariableAnnotation(): void
    {
        $apiVariable = new ApiVariable(value: 'test_value');

        $this->assertEquals('test_value', $apiVariable->value);
    }

    public function testBaseParamGetIn(): void
    {
        $apiHeader = new ApiHeader(name: 'test');
        $this->assertEquals('header', $apiHeader->getIn());

        $apiFormData = new ApiFormData(name: 'test');
        $this->assertEquals('formData', $apiFormData->getIn());
    }

    public function testApiAnnotationAttributeTargets(): void
    {
        $apiReflection = new ReflectionClass(Api::class);
        $attributes = $apiReflection->getAttributes();

        $foundAttribute = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Attribute') {
                $foundAttribute = true;
                $args = $attribute->getArguments();
                $this->assertContains(Attribute::TARGET_CLASS, $args);
            }
        }
        $this->assertTrue($foundAttribute);
    }

    public function testPhpTypeEnumValues(): void
    {
        $this->assertEquals('bool', PhpType::BOOL->getValue());
        $this->assertEquals('float', PhpType::FLOAT->getValue());
        $this->assertEquals('string', PhpType::STRING->getValue());
        $this->assertEquals('array', PhpType::ARRAY->getValue());
        $this->assertEquals('object', PhpType::OBJECT->getValue());
        $this->assertEquals('int', PhpType::INT->getValue());
    }
}
