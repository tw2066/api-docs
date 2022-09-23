<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\Contract\ConfigInterface;
use Hyperf\DTO\Mapper;

class SwaggerConfig
{
    private bool $enable = false;

    private string $default_parsing = '';

    private string $output_dir = '';

    private string $prefix_url = '';

    private bool $enable_default_security = false;

    private array $security_api = [];

    private bool $validation_custom_attributes = false;

    private array $responses = [];

    private array $swagger = [];

    private string $responses_code = '200';



    public function __construct(ConfigInterface $config)
    {
        $data = $config->get('api_docs');
        Mapper::map($data, $this);
    }

    /**
     * @param string $prefix_url
     */
    public function setPrefixUrl(string $prefix_url): void
    {
        $this->prefix_url = '/' . trim($prefix_url, '/');;
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }

    /**
     * @return string
     */
    public function getDefaultParsing(): string
    {
        return $this->default_parsing;
    }

    /**
     * @return string
     */
    public function getOutputDir(): string
    {
        return $this->output_dir;
    }

    /**
     * @return string
     */
    public function getPrefixUrl(): string
    {
        return $this->prefix_url;
    }

    /**
     * @return bool
     */
    public function isEnableDefaultSecurity(): bool
    {
        return $this->enable_default_security;
    }

    /**
     * @return array
     */
    public function getSecurityApi(): array
    {
        return $this->security_api;
    }

    /**
     * @return bool
     */
    public function isValidationCustomAttributes(): bool
    {
        return $this->validation_custom_attributes;
    }

    /**
     * @return array
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * @return array
     */
    public function getSwagger(): array
    {
        return $this->swagger;
    }

    /**
     * @return string
     */
    public function getResponsesCode(): string
    {
        return $this->responses_code;
    }

}