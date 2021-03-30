<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiHeader;
use Hyperf\ApiDocs\Annotation\ApiFormData;
use Hyperf\Apidocs\Annotation\ApiResponse;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\ApiDocs\ApiAnnotation;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class SwaggerJson
{

    private static array $className;

    private static array $simpleClassName;

    public $config;

    public static $swagger = [];

    public $stdoutLogger;

    public string $serverName;
    /**
     * @var MethodDefinitionCollectorInterface|mixed
     */
    private $methodDefinitionCollector;

    private ContainerInterface $container;


    public function __construct(string $serverName)
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        self::$swagger = $this->config->get('apidocs.swagger');
        $this->serverName = $serverName;
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->initModel();
        $this->securityKey();
    }

    /**
     * security
     */
    private function securityKey(){
        $securityKeyArr = $this->config->get('apidocs.security_api_key',[]);
        if(empty($securityKeyArr)){
            return;
        }
        $securityDefinitions = [];
        foreach ($securityKeyArr as $value) {
            $securityDefinitions[$value] = [
                    "type" => "apiKey",
                    "name" => $value,
                    "in" => "header",
            ];
        }
        self::$swagger['securityDefinitions'] = $securityDefinitions;
    }

    private function securityMethod(){
        $securityKeyArr = $this->config->get('apidocs.security_api_key',[]);
        if(empty($securityKeyArr)){
            return;
        }
        $security = [];
        foreach ($securityKeyArr as $value) {
            $security[] = [
                $value=>[]
            ];
        }
        return $security;
    }

    public function addPath($methods, $route, $className, $methodName)
    {
        //todo
        if($className != 'App\Controller\DemoController'){
//                return;
        }
        $classAnnotation = ApiAnnotation::classMetadata($className);
        /** @var Api $apiControllerAnnotation */
        $apiControllerAnnotation = $classAnnotation[Api::class] ?? new Api();
        /** @var Api $apiHeaderControllerAnnotation */
        $apiHeaderControllerAnnotation = $classAnnotation[ApiHeader::class] ?? null;
        //AutoController Annotation POST
        $autoControllerAnnotation = $classAnnotation[AutoController::class] ?? null;
        if ($autoControllerAnnotation && $methods != 'POST') {
            return;
        }
        $methodAnnotations = ApiAnnotation::methodMetadata($className, $methodName);
        $apiHeaderArr = $apiHeaderControllerAnnotation ? [$apiHeaderControllerAnnotation] : [];
        $apiOperation = new ApiOperation();
        $apiFormDataArr = [];
        $apiResponseArr = [];
        foreach ($methodAnnotations as $option) {
            /** @var ApiOperation $apiOperationAnnotation */
            if ($option instanceof ApiOperation) {
                $apiOperation = $option;
            }
            if ($option instanceof ApiHeader) {
                $apiHeaderArr[] = $option;
            }
            if ($option instanceof ApiFormData) {
                $apiFormDataArr[] = $option;
            }
            if ($option instanceof ApiResponse) {
                $apiResponseArr[] = $option;
            }
        }

        $tags = $apiControllerAnnotation->tags ?: [$this->getSimpleClassName($className)];
        foreach ($tags as $tag) {
            self::$swagger['tags'][$tag] = [
                'name' => $tag,
                'position' => $apiControllerAnnotation->position,
                'description' => $apiControllerAnnotation->description,
            ];
        }

        $method = strtolower($methods);
        $makeParameters = new MakeParameters($route, $method, $className, $methodName,$apiHeaderArr,$apiFormDataArr);
        $makeResponses = new MakeResponses($className, $methodName,$apiResponseArr,$this->config->get('apidocs'));
        self::$swagger['paths'][$route][$method] = [
            'tags' => $tags,
            'summary' => $apiOperation->summary ?? '',
            'description' => $apiOperation->description ?? '',
            'operationId' => implode('', array_map('ucfirst', explode('/', $route))) . $methods,
            'parameters' => $makeParameters->make(),
            'produces' => [
                'application/json',
            ],
            'responses' => $makeResponses->make(),
            'security'=> $this->securityMethod(),
        ];

    }


    public static function getSimpleClassName($className)
    {
        $className = '\\' . $className;
        if (isset(self::$className[$className])) {
            return self::$className[$className];
        }
        $simpleClassName = substr($className, strrpos($className, '\\') + 1);
        if (isset(self::$simpleClassName[$simpleClassName])) {
            $simpleClassName .= ++self::$simpleClassName[$simpleClassName];
        } else {
            self::$simpleClassName[$simpleClassName] = 0;
        }
        self::$className[$className] = $simpleClassName;
        return $simpleClassName;
    }

    private function putFile(string $file, string $content)
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

    private function sortByDesc(array $data){
        return  collect($data)
                ->sortByDesc('position')
                ->map(function ($item) {
                    return collect($item)->except('position');
                })
                ->values()
                ->toArray();
    }

    public function save()
    {
        self::$swagger['tags'] = $this->sortByDesc(self::$swagger['tags'] ?? []);
        $outputDir = $this->config->get('apidocs.output_dir');
        if (!$outputDir) {
            $this->stdoutLogger->error('/config/autoload/apidocs.php need set output_dir');
            return;
        }
        $outputFile = $outputDir . '/' . $this->serverName . '.json';
        $this->putFile($outputFile, json_encode(self::$swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        self::$swagger = [];
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
        self::$swagger['definitions']['ModelArray'] = $arraySchema;
        self::$swagger['definitions']['ModelObject'] = $objectSchema;
    }
}
