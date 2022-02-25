<?php

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\Database\Model\Model;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Scan\Property;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use HyperfExample\ApiDocs\DTO\Response\ActivityPage;

class GenerateDefinitions
{

    protected static array $definitions;

    private SwaggerCommon $swaggerCommon;

    public function __construct(SwaggerCommon $swaggerCommon)
    {
        static::$definitions = [];
        $this->swaggerCommon = $swaggerCommon;
    }

    public function getDefinitions(): array
    {
        $definitions = static::$definitions;
        static::$definitions = [];
        return $definitions;
    }

    public function getItems(Property $property): array
    {
//
//        $phpType = $property->phpType;
        $items = [];
        $swaggerType = $this->swaggerCommon->getType2SwaggerType($property->phpType);
        $className = $property->className;

        //简单类型
        if($property->isSimpleType){
            $items['type'] = $swaggerType;
            if($swaggerType == 'array'){
                $items['items']['type'] = 'string';
            }
            return $items;
        }

        if($swaggerType == 'array'){
            $items['type'] = 'array';
            if(!empty($className)){
                $items['items']['$ref'] = $this->swaggerCommon->getDefinitionName($className);
            }
            if(!empty($property->arrayType)){
                $items['items']['type'] = $swaggerType;
            }
            $this->generateClass2Schema($className);
        }else if(!empty($className)){
            $items['$ref'] = $this->swaggerCommon->getDefinitionName($className);
            $this->generateClass2Schema($className);
        }

        return $items;
    }

    public function generateClass2Schema(string $className): void
    {
        //generateDefinitions
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $simpleClassName = $this->swaggerCommon->getSimpleClassName($className);
        if (! ApplicationContext::getContainer()->has($className)) {
            static::$definitions[$simpleClassName] = $schema;
            return;
        }
        $obj = ApplicationContext::getContainer()->get($className);
        if ($obj instanceof Model) {
            static::$definitions[$simpleClassName] = $schema;
            return;
        }
        $rc = ReflectionManager::reflectClass($className);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $propertyClass = PropertyManager::getProperty($className, $fieldName);
//            dd(        ActivityPage::class,'content',
//            PropertyManager::getProperty(ActivityPage::class, 'content')
//            );
            $phpType = $propertyClass->phpType;
            $type = $this->swaggerCommon->getType2SwaggerType($phpType);
            $apiModelProperty = ApiAnnotation::getProperty($className, $fieldName, ApiModelProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiModelProperty();
            /** @var In $inAnnotation */
            $inAnnotation = ApiAnnotation::getProperty($className, $reflectionProperty->getName(), In::class);

            if ($apiModelProperty->hidden) {
                continue;
            }
            $property = [];
            $property['type'] = $type;
            if (! empty($inAnnotation)) {
                $property['enum'] = $inAnnotation->getValue();
            }
            $property['description'] = $apiModelProperty->value ?? '';
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
            }
            if ($apiModelProperty->example !== null) {
                //todo delete ???
                $property['example'] = $apiModelProperty->example;
            }
            if ($reflectionProperty->isPublic() && $reflectionProperty->isInitialized($obj)) {
                $property['default'] = $reflectionProperty->getValue($obj);
            }



//            if ($phpType == 'array') {
//                if ($propertyClass->className == null) {
//                    $property['items'] = (object) [];
//                } else {
//                    if ($propertyClass->isSimpleType) {
//                        $property['items']['type'] = $this->swaggerCommon->getType2SwaggerType($propertyClass->className);
//                    } else {
//                        $this->generateClass2Schema($propertyClass->className);
//                        $property['items']['$ref'] = $this->swaggerCommon->getDefinitionName($propertyClass->className);
//                    }
//                }
//            }
//
//
//            if ($type == 'object') {
//                $property['items'] = (object) [];
//            }
//            if (! $propertyClass->isSimpleType && $phpType != 'array' && class_exists($propertyClass->className)) {
//                $this->generateClass2Schema($propertyClass->className);
//                $property['$ref'] = $this->swaggerCommon->getDefinitionName($propertyClass->className);
//            }
//            if(!$propertyClass->isSimpleType){
//
//            }
            $items = $this->getItems($propertyClass);
            $property = Arr::merge($property,$items);
            $schema['properties'][$fieldName] = $property;
        }
        static::$definitions[$simpleClassName] = $schema;
    }


}