<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Tang\ApiDocs\Swagger;

use Hyperf\Di\ReflectionType;
use ReflectionProperty;
use Tang\ApiDocs\Annotation\Api;
use Tang\ApiDocs\Annotation\ApiModelProperty;
use Tang\ApiDocs\Annotation\ApiOperation;
use Tang\DTO\Contracts\RequestBody;
use Tang\DTO\Contracts\RequestFormData;
use Tang\DTO\Contracts\RequestQuery;
use Doctrine\Common\Annotations\AnnotationReader;
use JsonMapper;
use Tang\ApiDocs\ApiAnnotation;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Throwable;

class SwaggerJson extends JsonMapper
{
    public $config;

    public $swagger;

    public $logger;

    public $server;
    /**
     * @var MethodDefinitionCollectorInterface|mixed
     */
    private $methodDefinitionCollector;

    private ContainerInterface $container;

    private array $className;

    private array $simpleClassName;

    public function __construct(string $server)
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->logger = $this->container->get(LoggerFactory::class)->get('apidocs');
        $this->swagger = $this->config->get('apidog.swagger');
        $this->server = $server;
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->initModel();
    }

    public function addPath($methods, $route, $className, $methodName)
    {
        $ignores = $this->config->get('annotations.scan.ignore_annotations', []);
        foreach ($ignores as $ignore) {
            AnnotationReader::addGlobalIgnoredName($ignore);
        }
        $classAnnotation = ApiAnnotation::classMetadata($className);
        /** @var Api $controlerAnno */
        $controlerAnno = $classAnnotation[Api::class] ?? null;

        $bindServer = $this->config->get('server.servers.0.name');

        $servers = $this->config->get('server.servers');
        $servers_name = array_column($servers, 'name');
        if (!in_array($bindServer, $servers_name)) {
            throw new \Exception(sprintf('The bind ApiServer name [%s] not found, defined in %s!', $bindServer, $className));
        }

        if ($bindServer !== $this->server) {
            return;
        }

        $methodAnnotations = ApiAnnotation::methodMetadata($className, $methodName);

        /** @var ApiOperation $apiOperation */
        $apiOperation = null;
        $consumes = null;
        foreach ($methodAnnotations as $option) {
            /** @var ApiOperation $apiOperationAnnotation */
            if ($option instanceof ApiOperation) {
                $apiOperation = $option;
            }
        }

        $tag = $controlerAnno->tag ?: $className;
        $this->swagger['tags'][$tag] = [
            'name' => $tag,
            'description' => $controlerAnno->description,
        ];

        $method = strtolower($methods);
        $this->swagger['paths'][$route][$method] = [
            'tags' => [$tag],
            'summary' => $apiOperation->summary ?? '',
            'description' => $apiOperation->description ?? '',
            'operationId' => implode('', array_map('ucfirst', explode('/', $route))) . $methods,
            'parameters' => $this->makeParameters($route, $method, $className, $methodName),
            'produces' => [
                'application/json',
            ],
            'responses' => $this->makeResponses($className, $methodName),
        ];

    }

    private function makePropertyByClass(string $parameterClassName, string $in)
    {
        $parameters = [];
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $property = [];
            $property['in'] = $in;
            $property['name'] = $reflectionProperty->getName();
            try {
                $property['default'] = $reflectionProperty->getValue(make($parameterClassName));
            } catch (Throwable $exception) {
            }
            $property['type'] = $this->type2SwaggerType($this->getTypeName($reflectionProperty));
            /** @var ApiModelProperty $apiModelProperty */
            $apiModelProperty = null;
            $propertyReflectionPropertys = ApiAnnotation::propertyMetadata($parameterClassName, $reflectionProperty->getName());
            foreach ($propertyReflectionPropertys as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
            }
            $property['description'] = $apiModelProperty->description ?? '';
            $parameters[] = $property;
        }
        return $parameters;
    }

    public function makeParameters($route, $method, $controller, $action)
    {
        $parameters = [];
        $consumes = null;
        $definitions = $this->methodDefinitionCollector->getParameters($controller, $action);
        foreach ($definitions as $k => $definition) {
            $parameterClassName = $definition->getName();
            if ($parameterClassName == 'int') {
                $property = [];
                $property['in'] = 'path';
                $property['name'] = $definition->getMeta('name');
                $property['required'] = true;
                $property['type'] = 'integer';
                $parameters[] = $property;
                continue;
            }
            if ($parameterClassName == 'string') {
                $property = [];
                $property['in'] = 'path';
                $property['name'] = $definition->getMeta('name');
                $property['required'] = true;
                $property['type'] = 'string';
                $parameters[] = $property;
                continue;
            }

            if ($this->container->has($parameterClassName)) {
                $obj = $this->container->get($parameterClassName);
                if ($obj instanceof RequestBody) {
                    $this->class2schema($parameterClassName);
                    $property = [];
                    $property['in'] = 'body';
                    $property['name'] = $this->getSimpleClassName($parameterClassName);
                    $property['description'] = '';
                    $property['schema']['$ref'] = $this->getDefinitions($parameterClassName);

                    $parameters[] = $property;
                    $consumes = 'application/json';
                }
                if ($obj instanceof RequestQuery) {
                    $propertyArr = $this->makePropertyByClass($parameterClassName, 'query');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($obj instanceof RequestFormData) {
                    $propertyArr = $this->makePropertyByClass($parameterClassName, 'formData');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                    $consumes = 'application/x-www-form-urlencoded';
                }
            }

            if ($consumes !== null) {
                $this->swagger['paths'][$route][$method]['consumes'] = [$consumes];
            }

        }
        return array_values($parameters);
    }

    private function getTypeName(ReflectionProperty $rp)
    {
        try {
            $type = $rp->getType()->getName();
        }catch (Throwable $throwable){
            $type = 'string';
        }
        return $type;
    }

    private function allowsNull(ReflectionProperty $rp) : bool
    {
        try {
            $bool = !$rp->getType()->allowsNull();
        }catch (Throwable $throwable){
            $bool = false;
        }
        return $bool;
    }


    private function getDefinitions($className){
        return '#/definitions/'.$this->getSimpleClassName($className);
    }

    private function getSimpleClassName($className){

        $className = '\\' . $className;

        if(isset($this->className[$className])){
            return $this->className[$className];
        }
        $simpleClassName =  substr($className,strrpos($className,'\\')+1);

        if(isset($this->simpleClassName[$simpleClassName])){
            $simpleClassName .= ++$this->simpleClassName[$simpleClassName];
        }else{
            $this->simpleClassName[$simpleClassName] = 0;
        }
        $this->className[$className] = $simpleClassName;
        return $simpleClassName;
    }

    public function class2schema($className)
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $rc = ReflectionManager::reflectClass($className);
        $strNs = $rc->getNamespaceName();
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $type = $this->getTypeName($reflectionProperty);
            $fieldName = $reflectionProperty->getName();
            $type = $this->type2SwaggerType($type);
            /** @var ApiModelProperty $apiModelProperty */
            $apiModelProperty = null;
            $propertyReflectionPropertys = ApiAnnotation::propertyMetadata($className, $fieldName);
            foreach ($propertyReflectionPropertys as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
            }

            $property = [];
            $property['type'] = $type;
            $property['description'] = $apiModelProperty->description ?? '';
            //$property['example'] = null;
            $property['required'] = $this->allowsNull($reflectionProperty);

            if ($type == 'array') {
                $docblock = $reflectionProperty->getDocComment();
                $annotations = static::parseAnnotations($docblock);
                if(empty($annotations)){
                    $property['items']['$ref'] = '#/definitions/ModelArray';
                }else{
                    //support "@var type description"
                    list($type) = explode(' ', $annotations['var'][0]);
                    $type = $this->getFullNamespace($type, $strNs);
                    if ($this->isArrayOfType($type)) {
                        $subtype = substr($type, 0, -2);
                        if ($this->isSimpleType($subtype)) {
                            $property['items']['type'] = $this->type2SwaggerType($subtype);
                        } else {
                            $this->class2schema($subtype);
                            $property['items']['$ref'] = $this->getDefinitions($subtype);
                        }
                    }
                }
            }
            if ($type == 'object') {
                $property['items']['$ref'] = '#/definitions/ModelObject';
            }

            if (!$this->isSimpleType($type) && class_exists($type)) {
                $this->class2schema($type);
                $property['$ref'] = $this->getDefinitions($type);
            }

            $schema['properties'][$fieldName] = $property;
        }
        $this->swagger['definitions'][$this->getSimpleClassName($className)] = $schema;

    }


    private function type2SwaggerType($phpType)
    {
        switch ($phpType) {
            case 'int':
            case 'integer':
                $type = 'integer';
                break;
            case 'boolean':
            case 'bool':
                $type = 'boolean';
                break;
            case 'array':
                $type = 'array';
                break;
            case 'object':
                $type = 'object';
                break;
            default:
                $type = 'string';
        }
        return $type;
    }

    public function makeResponses($className, $methodName)
    {
        /** @var ReflectionType $definitions */
        $definition = $this->methodDefinitionCollector->getReturnType($className, $methodName);
        $returnTypeClassName = $definition->getName();

        $resp["200"]['description'] = 'OK';
        if ($this->isSimpleType($returnTypeClassName)) {
            $type = $this->type2SwaggerType($returnTypeClassName);
            if ($type == 'array') {
                $resp['200']['schema']['items']['$ref'] = '#/definitions/ModelArray';
            }
            if ($type == 'object') {
                $resp['200']['schema']['items']['$ref'] = '#/definitions/ModelObject';
            }
            $resp['200']['schema']['type'] = $type;
        } else if ($this->container->has($returnTypeClassName)) {
            $this->class2schema($returnTypeClassName);
            $resp['200']['schema']['$ref'] = $this->getDefinitions($returnTypeClassName);
        }
        return $resp;
    }

    public function putFile(string $file, string $content)
    {
        $pathInfo = pathinfo($file);
        if (! empty($pathInfo['dirname'])) {
            if (file_exists($pathInfo['dirname']) === false) {
                if (mkdir($pathInfo['dirname'], 0644, true) === false) {
                    return false;
                }
            }
        }
        return file_put_contents($file, $content);
    }

    public function save()
    {

        $this->swagger['tags'] = array_values($this->swagger['tags'] ?? []);
        $outputFile = $this->config->get('apidocs.output_dir');
        if (! $outputFile) {
            $this->logger->error('/config/autoload/apidog.php need set output_file');
            return;
        }
        $outputFile = $outputFile.'/'.$this->server.'.json';
        $this->putFile($outputFile, json_encode($this->swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->logger->debug('Generate swagger.json success!');
        return $outputFile;

    }


    private function initModel()
    {
        $arraySchema = [
            'type' => 'array',
            'required' => [],
            'items' => [
            ],
        ];
        $objectSchema = [
            'type' => 'object',
            'required' => [],
            'items' => [
            ],
        ];

        $this->swagger['definitions']['ModelArray'] = $arraySchema;
        $this->swagger['definitions']['ModelObject'] = $objectSchema;
    }
}
