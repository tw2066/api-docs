<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\ApiDocs\Collect\ResponseInfo;
use Hyperf\ApiDocs\Collect\Schema;
use Hyperf\ApiDocs\Collect\SchemaItems;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class GenerateResponses
{
    private string $className;

    private string $methodName;

    private SwaggerCommon $common;

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    private array $config;

    private array $apiResponseArr;

    public function __construct(string $className, string $methodName, array $apiResponseArr, array $config)
    {
        $this->container = ApplicationContext::getContainer();
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->className = $className;
        $this->methodName = $methodName;
        $this->config = $config;
        $this->apiResponseArr = $apiResponseArr;
        $this->common = new SwaggerCommon();
    }

    public function generate(): array
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($this->className, $this->methodName);
        $returnTypeClassName = $definition->getName();
        $code = $this->config['responses_code'] ?? 200;
//        $resp[$code] = $this->getSchema($returnTypeClassName);
//        $resp[$code]['description'] = 'OK';

        //TODO 处理
//        $globalResp = $this->getGlobalResp();
//        $AnnotationResp = $this->getAnnotationResp();

        $arr = [];
//        $responseInfo = new ResponseInfo();
//        $responseInfo->httpCode = (string)$code;

//        $responseInfo->schema = $this->getSchema($returnTypeClassName);

        $responseInfo = $this->getResponseInfo($returnTypeClassName);
        $responseInfo->httpCode = (string)$code;
        $responseInfo->description = 'OK';
        $arr[] = $responseInfo;
//        dd($responseInfo);
//        $arr[] = $globalResp;
//        $arr[] = $AnnotationResp;

        return $arr;
    }
    private function getResponseInfo(string $returnTypeClassName): ResponseInfo
    {

        $responseInfo = new ResponseInfo();
//        $schema = [];
//        $schema = new Schema();
//        $schemaItems = new SchemaItems();
        if ($this->common->isSimpleType($returnTypeClassName)) {
            $responseInfo->isSimpleType = true;

            $responseInfo->phpType = $returnTypeClassName;


//            $type = $this->common->getType2SwaggerType($returnTypeClassName);
//            if ($type == 'array') {
//                $responseInfo->type = 'array';
////                $schema['schema']['items'] = (object) [];
//            }
//            if ($type == 'object') {
//                $responseInfo->type = 'object';
////                $schema['schema']['items'] = (object) [];
//            }
////            $schema['schema']['type'] = $type;

//            $responseInfo->type = $type;
//            $schemaItems->type = $type;
//            $schema->items = $schemaItems;
        } elseif ($this->container->has($returnTypeClassName)) {
            $responseInfo->isSimpleType = false;
            $responseInfo->className = $returnTypeClassName;
//            $this->common->generateClass2schema($returnTypeClassName);
//            $schema['schema']['$ref'] = $this->common->getDefinitions($returnTypeClassName);

//            $schema->type = 'object';
//
//
//            $schemaItems->ref = $this->common->getDefinitions($returnTypeClassName);
//            $schema->items = $schemaItems;
        }
        return $responseInfo;
    }


    private function getSchema2($returnTypeClassName): Schema
    {
//        $schema = [];
        $schema = new Schema();
        $schemaItems = new SchemaItems();
        if ($this->common->isSimpleType($returnTypeClassName)) {
            $type = $this->common->getType2SwaggerType($returnTypeClassName);
            if ($type == 'array') {
                $schema->type = 'array';
//                $schema['schema']['items'] = (object) [];
            }
            if ($type == 'object') {
                $schema->type = 'object';
//                $schema['schema']['items'] = (object) [];
            }
//            $schema['schema']['type'] = $type;
            //TODO ???
            $schema->type = $type;
//            $schemaItems->type = $type;
//            $schema->items = $schemaItems;
        } elseif ($this->container->has($returnTypeClassName)) {
//            $this->common->generateClass2schema($returnTypeClassName);
//            $schema['schema']['$ref'] = $this->common->getDefinitions($returnTypeClassName);

            $schema->type = 'object';


            $schemaItems->ref = $this->common->getDefinitionName($returnTypeClassName);
            $schema->items = $schemaItems;
        }
        return $schema;
    }

    private function getGlobalResp(): array
    {
        $resp = [];
        foreach ($this->config['responses'] as $code => $value) {
            isset($value['className']) && $resp[$code] = $this->getSchema($value['className']);
            $resp[$code]['description'] = $value['description'];
        }
        return $resp;
    }

    private function getAnnotationResp(): array
    {
        $resp = [];
        /** @var ApiResponse $apiResponse */
        foreach ($this->apiResponseArr as $apiResponse) {
            if ($apiResponse->className) {
                $scanAnnotation = $this->container->get(ScanAnnotation::class);
                $scanAnnotation->scanClass($apiResponse->className);
                $this->getSchema($apiResponse->className);
                if (! empty($apiResponse->type)) {
                    $schema['schema']['type'] = $apiResponse->type;
                    $schema['schema']['items']['$ref'] = $this->getSchema($apiResponse->className)['schema']['$ref'];
                    $resp[$apiResponse->code] = $schema;
                } else {
                    $resp[$apiResponse->code] = $this->getSchema($apiResponse->className);
                }
            }
            $resp[$apiResponse->code]['description'] = $apiResponse->description;
        }
        return $resp;
    }
}
