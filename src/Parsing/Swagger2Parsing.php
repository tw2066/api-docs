<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Collect\MainCollect;
use Hyperf\ApiDocs\Collect\ParameterInfo;
use Hyperf\ApiDocs\Collect\ResponseInfo;
use Hyperf\ApiDocs\Collect\RouteCollect;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;

class Swagger2Parsing implements ParsingInterface
{


    private SwaggerCommon $swaggerCommon;
    private GenerateDefinitions $generateDefinitions;

    public function __construct()
    {
        $this->swaggerCommon = new SwaggerCommon();
        $this->generateDefinitions = new GenerateDefinitions($this->swaggerCommon);
    }

    /**
     * @param RouteCollect[] $routes
     */
    public function parsing(array $mainInfo, array $routes, array $tags, array $definitionClass): array
    {
        $swagger = $mainInfo;
        ////        self::$swagger['paths'][$route][$method] = [
        ////            'tags' => $tags,
        ////            'summary' => $apiOperation->summary ?? '',
        ////            'description' => $apiOperation->description ?? '',
        ////            'deprecated' => $isDeprecated,
        ////            'operationId' => implode('', array_map('ucfirst', explode('/', $route))) . $methods,
        ////            'parameters' => $parameters,
        ////            'consumes' => $consumes,
        ////            'produces' => [
        ////                '*/*',
        ////            ],
        ////            'responses' => $makeResponses->generate(),
        ////            'security' => $this->securityMethod(),
        ////        ];
        foreach ($routes as $route) {
            $swagger['paths'][$route->route][$route->requestMethod] = [
                'tags' => $route->tags,
                'summary' => $route->summary ?? '',
                'description' => $route->description ?? '',
                'deprecated' => $route->deprecated,
                'operationId' => $route->operationId,
                'parameters' => $this->getParameters($route->parameters),
                'consumes' => $route->consumes,
                'produces' => [
                    '*/*',
                ],
                'responses' => $this->getResponses($route->responses),
                'security' => $route->security,
            ];
        }
        $swagger['tags'] = $tags;

        $swagger['definitions'] = $this->generateDefinitions->getDefinitions();
        return $this->sort($swagger);
    }

    /**
     * @param ParameterInfo[] $parameters
     */
    protected function getParameters(array $parameters): array
    {
        $data = [];
        foreach ($parameters as $parameterInfo) {
            $property = [];
            $property['name'] = $parameterInfo->name;
            $property['in'] = $parameterInfo->in;
            $parameterInfo->description !== null && $property['description'] = $parameterInfo->description;
            $parameterInfo->required !== null && $property['required'] = $parameterInfo->required;
            $parameterInfo->type !== null && $property['type'] = $parameterInfo->type;
            $parameterInfo->default !== null && $property['default'] = $parameterInfo->default;

            $parameterInfo->example !== null && $property['example'] = $parameterInfo->example;

            $parameterInfo->enum !== null && $property['enum'] = $parameterInfo->enum;

//            if($parameterInfo->isSimpleType){
//                $property['schema']['type'] = $this->swaggerCommon->getSimpleType2SwaggerType($parameterInfo->phpType);
//            }else if(!empty($parameterInfo->className)){
//                $property['schema']['$ref'] = $this->swaggerCommon->getDefinitions($parameterInfo->className);
//            }


//            $swaggerType = $this->swaggerCommon->getSimpleType2SwaggerType($parameterInfo->phpType);
//            if(!empty($swaggerType)){
//                $property['schema']['type'] = $swaggerType;
//            }
//            $className = $parameterInfo->className;
            if ($parameterInfo->property){
//                $property['schema'] = $this->generateDefinitions->getItems($parameterInfo->phpType,$className,$parameterInfo->isSimpleType);
                $property['schema'] = $this->generateDefinitions->getItems($parameterInfo->property);
            }
//            if($parameterInfo->phpType == 'array' && !empty($className)){
//                $this->generateDefinitions->generateClass2Schema($className);
//                $property['schema']['items']['$ref'] = $this->swaggerCommon->getDefinitionName($className);
//            }else if(!empty($className)){
//                $this->generateDefinitions->generateClass2Schema($className);
//                $property['schema']['$ref'] = $this->swaggerCommon->getDefinitionName($className);
//            }

//            $schema = $parameterInfo->schema;
//            if ($schema !== null) {
//                if ($schema->items !== null) {
//                    $schema->items->ref && $property['schema']['$ref'] = $schema->items->ref;
//                } else {
//                    $schema->type && $property['schema']['type'] = $schema->type;
//                }
//            }
            $data[] = $property;
        }
        return $data;
    }

    /**
     * @param ResponseInfo[] $responses
     */
    protected function getResponses(array $responses): array
    {
//        dd($responses);
        $data = [];
        foreach ($responses as $responseInfo) {
            $tmp=[];
            $tmp['description'] = $responseInfo->description;


            $className = $responseInfo->className;
//            dd($this->generateDefinitions->getItems($responseInfo->phpType,$className,$responseInfo->isSimpleType));
            $tmp['schema'] = $this->generateDefinitions->getItems($responseInfo->property);



            //                    "200": {
            //                        "description": "success",
            //                        "schema": {
            //                            "type": "integer"
            //                        }
            //                    }
//            $swaggerType = $this->swaggerCommon->getSimpleType2SwaggerType($responseInfo->phpType);
//            if(!empty($swaggerType)){
//                $tmp['schema']['type'] = $swaggerType;
//            }
//            if($responseInfo->phpType == 'array' && !empty($responseInfo->className)){
//                $tmp['schema']['items']['$ref'] = $this->swaggerCommon->getDefinitionName($responseInfo->className);
//            }else if(!empty($responseInfo->className)){
//                $tmp['schema']['$ref'] = $this->swaggerCommon->getDefinitionName($responseInfo->className);
//            }

//            if($responseInfo->isSimpleType){
//                $tmp['schema']['type'] = $this->swaggerCommon->getSimpleType2SwaggerType($responseInfo->phpType);
//            }else if($responseInfo->phpType == 'array' && !empty($responseInfo->className)){
//                $tmp['schema']['items']['$ref'] = $this->swaggerCommon->getDefinitions($responseInfo->className);
//            }else if(!empty($responseInfo->className)){
//                $tmp['schema']['$ref'] = $this->swaggerCommon->getDefinitions($responseInfo->className);
//            }
            //$schema['schema']['type']
//
//
//            $schema = $responseInfo->schema;
//            if ($schema != null) {
//                $schema->type && $tmp['schema']['type'] = $schema->type;
//                if ($schema->items !== null) {
//                    $schema->items->ref && $tmp['schema']['$ref'] = $schema->items->ref;
//                    $schema->items->type && $tmp['schema']['type'] = $schema->items->type;
//                }
//            }
            $data[$responseInfo->httpCode] = $tmp;
        }
        return $data;
    }



    /**
     * sort.
     */
    protected function sort(array $data): array
    {
        $data['tags'] = collect($data['tags'] ?? [])
            ->sortByDesc('position')
            ->map(function ($item) {
                return collect($item)->except('position');
            })
            ->values()
            ->toArray();
        $data['paths'] = collect($data['paths'] ?? [])
            ->sortBy('position')
            ->map(function ($item) {
                return collect($item)->except('position');
            })
            ->toArray();
        return $data;
    }
}
