<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\ReflectionType;
use Hyperf\HttpServer\Annotation\AutoController;
use ReflectionProperty;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\DTO\Contracts\RequestBody;
use Hyperf\DTO\Contracts\RequestFormData;
use Hyperf\DTO\Contracts\RequestQuery;
use JsonMapper;
use Hyperf\ApiDocs\ApiAnnotation;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Throwable;

class SwaggerJson extends JsonMapper
{
    public $config;

    public $swagger;

    public $stdoutLogger;

    public string $serverName;
    /**
     * @var MethodDefinitionCollectorInterface|mixed
     */
    private $methodDefinitionCollector;

    private ContainerInterface $container;

    private array $className;

    private array $simpleClassName;

    public function __construct(string $serverName)
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        $this->swagger = $this->config->get('apidocs.swagger');
        $this->serverName = $serverName;
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->initModel();
    }

    public function addPath($methods, $route, $className, $methodName)
    {
        $classAnnotation = ApiAnnotation::classMetadata($className);
        /** @var Api $controlerAnno */
        $controlerAnno = $classAnnotation[Api::class] ?? new Api();

        //AutoController Annotation POST
        $autoControllerAnno = $classAnnotation[AutoController::class] ?? null;
        if ($autoControllerAnno && $methods != 'POST') {
            return;
        }

        $bindServer = $this->config->get('server.servers.0.name');
        $servers = $this->config->get('server.servers');
        $servers_name = array_column($servers, 'name');
        if (!in_array($bindServer, $servers_name)) {
            throw new \Exception(sprintf('The bind ApiServer name [%s] not found, defined in %s!', $bindServer, $className));
        }

        if ($bindServer !== $this->serverName) {
            return;
        }

        $methodAnnotations = ApiAnnotation::methodMetadata($className, $methodName);

        $apiOperation = new ApiOperation();
        $consumes = null;
        foreach ($methodAnnotations as $option) {
            /** @var ApiOperation $apiOperationAnnotation */
            if ($option instanceof ApiOperation) {
                $apiOperation = $option;
            }
        }
        $tags = $controlerAnno->tags ?: [$this->getSimpleClassName($className)];

        foreach ($tags as $tag) {
            $this->swagger['tags'][$tag] = [
                'name' => $tag,
                'description' => $controlerAnno->description,
            ];
        }

        $method = strtolower($methods);
        $this->swagger['paths'][$route][$method] = [
            'tags' => $tags,
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
            $phpType = $this->getTypeName($reflectionProperty);
            $property['type'] = $this->type2SwaggerType($phpType);
            if (!in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
                continue;
            }
            $apiModelProperty = new ApiModelProperty();
            $propertyReflectionPropertyArr = ApiAnnotation::propertyMetadata($parameterClassName, $reflectionProperty->getName());
            foreach ($propertyReflectionPropertyArr as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
            }
            if ($apiModelProperty->hidden) {
                continue;
            }
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
            }
            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
            }
            $property['description'] = $apiModelProperty->value ?? '';
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
        } catch (Throwable $throwable) {
            $type = 'string';
        }
        return $type;
    }


    private function getDefinitions($className)
    {
        return '#/definitions/' . $this->getSimpleClassName($className);
    }

    private function getSimpleClassName($className)
    {

        $className = '\\' . $className;

        if (isset($this->className[$className])) {
            return $this->className[$className];
        }
        $simpleClassName = substr($className, strrpos($className, '\\') + 1);

        if (isset($this->simpleClassName[$simpleClassName])) {
            $simpleClassName .= ++$this->simpleClassName[$simpleClassName];
        } else {
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
            $apiModelProperty = new ApiModelProperty();
            $propertyReflectionPropertyArr = ApiAnnotation::propertyMetadata($className, $fieldName);
            foreach ($propertyReflectionPropertyArr as $propertyReflectionProperty) {
                if ($propertyReflectionProperty instanceof ApiModelProperty) {
                    $apiModelProperty = $propertyReflectionProperty;
                }
            }
            if ($apiModelProperty->hidden) {
                continue;
            }
            $property = [];
            $property['type'] = $type;
            $property['description'] = $apiModelProperty->value ?? '';
            if ($apiModelProperty->required !== null) {
                $property['required'] = $apiModelProperty->required;
            }
            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
            }
            if ($type == 'array') {
                $docblock = $reflectionProperty->getDocComment();
                $annotations = static::parseAnnotations($docblock);
                if (empty($annotations)) {
                    $property['items']['$ref'] = '#/definitions/ModelArray';
                } else {
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
            case 'double':
            case 'float':
                $type = 'number';
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
        if (!empty($pathInfo['dirname'])) {
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
        $outputDir = $this->config->get('apidocs.output_dir');
        if (!$outputDir) {
            $this->stdoutLogger->error('/config/autoload/apidocs.php need set output_dir');
            return;
        }
        $outputFile = $outputDir . '/' . $this->serverName . '.json';
        $this->putFile($outputFile, json_encode($this->swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->stdoutLogger->debug('Generate swagger.json success!');
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
