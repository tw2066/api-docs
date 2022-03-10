<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Parsing;

use Hyperf\ApiDocs\Collect\ParameterInfo;
use Hyperf\ApiDocs\Collect\ResponseInfo;
use Hyperf\ApiDocs\Collect\RouteCollect;
use Hyperf\ApiDocs\Parsing\Swagger2\GenerateDefinitions;
use Hyperf\ApiDocs\Swagger\GenerateParameters;
use Hyperf\ApiDocs\Swagger\SwaggerCommon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class Swagger2Parsing implements ParsingInterface
{
    use SwaggerCommon;

    private GenerateDefinitions $generateDefinitions;

    private ConfigInterface $config;

    private ContainerInterface $container;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->generateDefinitions = new GenerateDefinitions();
        $this->config = $this->container->get(ConfigInterface::class);
    }

    /**
     * @param RouteCollect[] $routes
     */
    public function parsing(array $mainInfo, array $routes, array $tags): array
    {
        $swagger = $mainInfo;
        foreach ($routes as $route) {
            $swagger['paths'][$route->route]['position'] = $route->position;
            $swagger['paths'][$route->route][$route->requestMethod] = [
                'tags' => $route->tags,
                'summary' => $route->summary ?? '',
                'description' => $route->description ?? '',
                'deprecated' => $route->deprecated,
                'operationId' => $route->operationId,
                'parameters' => $this->getParameters($route->parameters),
                'consumes' => $this->getConsumes($route->consumeTypes),
                'produces' => [
                    '*/*',
                ],
                'responses' => $this->getResponses($route->responses),
                'security' => $this->securityMethod($route->isSecurity),
            ];
        }
        $swagger['tags'] = $tags;
        $swagger['definitions'] = $this->generateDefinitions->getDefinitions();
        $securityDefinitions = $this->securityKey();
        $securityDefinitions && $swagger['securityDefinitions'] = $this->securityKey();
        return $this->sort($swagger);
    }

    protected function getConsumes(array $consumeTypes): array
    {
        $consumes = [];
        foreach ($consumeTypes as $consumeType) {
            switch ($consumeType) {
                case GenerateParameters::CONTENT_TYPE_JSON:
                    $consumes[] = 'application/json';
                    break;
                case GenerateParameters::CONTENT_TYPE_FORM:
                    $consumes[] = 'application/x-www-form-urlencoded';
                    break;
            }
        }
        return $consumes;
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
            $parameterInfo->enum !== null && $property['enum'] = $parameterInfo->enum;
            if ($parameterInfo->property) {
                $property['schema'] = $this->generateDefinitions->getItems($parameterInfo->property);
            }
            $data[] = $property;
        }
        return $data;
    }

    /**
     * @param ResponseInfo[] $responses
     */
    protected function getResponses(array $responses): array
    {
        $data = [];
        foreach ($responses as $responseInfo) {
            $tmp = [];
            $tmp['description'] = $responseInfo->description;
            $responseInfo->property && $tmp['schema'] = $this->generateDefinitions->getItems($responseInfo->property);
            $data[(string) $responseInfo->code] = $tmp;
        }
        return $data;
    }

    /**
     * 排序.
     */
    protected function sort(array $data): array
    {
        // 根据tags排序
        $data['tags'] = collect($data['tags'] ?? [])
            ->sortByDesc('position')
            ->map(function ($item) {
                return collect($item)->except('position');
            })
            ->values()
            ->toArray();
        // 根据方法的位置排序
        $data['paths'] = collect($data['paths'] ?? [])
            ->sortBy('position')
            ->map(function ($item) {
                return collect($item)->except('position');
            })
            ->toArray();
        return $data;
    }

    /**
     * security_api_key.
     */
    protected function securityMethod(bool $isSecurity): array
    {
        if (! $isSecurity) {
            return [];
        }
        $securityKeyArr = $this->config->get('api_docs.security_api', []);
        if (empty($securityKeyArr)) {
            return [];
        }
        $security = [];
        foreach ($securityKeyArr as $key => $value) {
            $security[] = [
                $key => [],
            ];
        }
        return $security;
    }

    /**
     * set security.
     */
    private function securityKey(): array
    {
        $securityKeyArr = $this->config->get('api_docs.security_api', []);
        if (empty($securityKeyArr)) {
            return [];
        }
        $securityDefinitions = [];
        foreach ($securityKeyArr as $key => $value) {
            $securityDefinitions[$key] = [
                'type' => $value['type'] ?? 'apiKey',
                'name' => $key,
                'in' => $value['in'] ?? 'header',
            ];
        }
        return $securityDefinitions;
    }
}
