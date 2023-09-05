<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\Contract\ConfigInterface;
use Hyperf\DTO\Mapper;

class SwaggerConfig
{
    private bool $enable = false;

    private string $output_dir = '';

    private string $proxy_dir = '';

    private string $prefix_url = '';

    private string $prefix_swagger_resources = 'https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.5.0';

    private bool $validation_custom_attributes = false;

    private array $responses = [];

    private array $swagger = [];

    private string $responses_code = '200';

    private string $format = 'json';

    private string $global_return_responses_class = '';

    public function __construct(ConfigInterface $config)
    {
        $data = $config->get('api_docs', []);
        $jsonMapper = Mapper::getJsonMapper('bIgnoreVisibility');
        // 私有属性和函数
        $jsonMapper->bIgnoreVisibility = true;
        $jsonMapper->map($data, $this);
    }

    public function setPrefixUrl(string $prefix_url): void
    {
        $this->prefix_url = '/' . trim($prefix_url, '/');
    }

    public function isEnable(): bool
    {
        return $this->enable;
    }

    public function getOutputDir(): string
    {
        return $this->output_dir;
    }

    public function getProxyDir(): string
    {
        return $this->proxy_dir ?: BASE_PATH . '/runtime/container/proxy/';
    }

    public function setProxyDir(string $proxy_dir): void
    {
        $this->proxy_dir = rtrim($proxy_dir, '/') . '/';
    }

    public function getPrefixUrl(): string
    {
        return $this->prefix_url ?: 'swagger';
    }

    public function isValidationCustomAttributes(): bool
    {
        return $this->validation_custom_attributes;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * @return array [
     *               'info' => [],
     *               'servers' => [],
     *               'externalDocs' => [],
     *               'components' => [
     *               'securitySchemes'=>[]
     *               ],
     *               'openapi'=>'',
     *               'security'=>[],
     *               ]
     */
    public function getSwagger(): array
    {
        return $this->swagger;
    }

    public function getResponsesCode(): string
    {
        return $this->responses_code;
    }

    public function getFormat(): string
    {
        return $this->format == 'json' ? 'json' : 'yaml';
    }

    public function getPrefixSwaggerResources(): string
    {
        return $this->prefix_swagger_resources;
    }

    public function getGlobalReturnResponsesClass(): string
    {
        return $this->global_return_responses_class;
    }
}
