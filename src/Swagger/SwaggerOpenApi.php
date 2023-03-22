<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Generator;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\DTO\Mapper;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\OpenApi;
use OpenApi\Attributes\Tag;
use OpenApi\Generator;
use SplPriorityQueue;

class SwaggerOpenApi
{
    public ?SplPriorityQueue $queueTags;

    private ?OpenApi $openApi = null;

    private ?SplPriorityQueue $queuePaths;

    private array $tags = [];

    private array $componentsSchemas = [];

    public function __construct(
        private SwaggerConfig $swaggerConfig,
    ) {
    }

    public function init(): void
    {
        $this->openApi = new OpenApi();
        $this->openApi->paths = [];
        $this->openApi->tags = [];
        $this->openApi->components = new OA\Components();
        $this->tags = [];
        $this->queuePaths = new SplPriorityQueue();
        $this->queueTags = new SplPriorityQueue();
        $this->setOpenapiVersion();
        $this->setInfo();
        $this->setServers();
        $this->setComponentsSecuritySchemes();
        $this->setSecurity();
        $this->setExternalDocs();
    }

    public function clean(): void
    {
        $this->openApi = null;
        $this->tags = [];
        $this->queuePaths = null;
        $this->queueTags = null;
    }

    public function getQueuePaths(): SplPriorityQueue
    {
        return $this->queuePaths;
    }

    /**
     * 设置OpenapiVersion.
     */
    public function setOpenapiVersion(): void
    {
        if (! empty($this->swaggerConfig->getSwagger()['openapi'])) {
            $this->openApi->openapi = $this->swaggerConfig->getSwagger()['openapi'];
        }
    }

    /**
     * 设置openApi对象 security.
     */
    public function setSecurity(): void
    {
        if (! empty($this->swaggerConfig->getSwagger()['security'])) {
            $this->openApi->security = $this->swaggerConfig->getSwagger()['security'];
        }
    }

    /**
     * 设置tags.
     */
    public function setTags(string $tagName, int $position, Tag $tag): void
    {
        if (isset($this->tags[$tagName])) {
            return;
        }
        $this->tags[$tagName] = true;
        $this->queueTags->insert($tag, $position);
    }

    public function setComponentsSchemas(array $componentsSchemas): void
    {
        $this->componentsSchemas = array_values($componentsSchemas);
    }

    public function setComponentsSecuritySchemes(): void
    {
        $securitySchemes = $this->swaggerConfig->getSwagger()['components']['securitySchemes'] ?? [];
        if ($securitySchemes) {
            $this->openApi->components->securitySchemes = [];
            foreach ($securitySchemes as $securityScheme) {
                $this->openApi->components->securitySchemes[] = Mapper::map($securityScheme, new OA\SecurityScheme());
            }
        }
    }

    public function save(string $serverName): void
    {
        $paths = [];
        // 设置paths
        while (! $this->queuePaths->isEmpty()) {
            $paths[] = $this->queuePaths->extract();
        }

        // 相同path不同method的特殊处理
        $validateMethods = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];
        $uniquePaths = [];
        foreach ($paths as $value) {
            $path = $value->path;
            if (isset($uniquePaths[$path])) {
                $hasSetMethods = array_filter($validateMethods, fn ($method) => $uniquePaths[$path]->$method !== Generator::UNDEFINED);
                $newMethods = array_filter($validateMethods, fn($method) => $value->$method !== Generator::UNDEFINED);
                $needAddMethods = array_diff($newMethods, $hasSetMethods);
                if (!empty($needAddMethods)) {
                    foreach ($needAddMethods as $method) {
                        $uniquePaths[$path]->$method = $value->$method;
                    }
                }
            } else {
                $uniquePaths[$path] = $value;
            }
        }

        $this->openApi->paths= $uniquePaths;
        // 设置tags
        while (! $this->queueTags->isEmpty()) {
            $this->openApi->tags[] = $this->queueTags->extract();
        }
        // 设置components->schemas
        $this->openApi->components->schemas = array_values($this->componentsSchemas);
        // 创建目录
        $outputDir = $this->swaggerConfig->getOutputDir();
        if (file_exists($outputDir) === false) {
            if (mkdir($outputDir, 0755, true) === false) {
                throw new ApiDocsException("Failed to create a directory : {$outputDir}");
            }
        }
        $outputFile = $outputDir . '/' . $serverName . '.' . $this->swaggerConfig->getFormat();
        $this->openApi->saveAs($outputFile);
    }

    private function setInfo(): void
    {
        $info = $this->swaggerConfig->getSwagger()['info'] ?? [];
        $this->openApi->info = Mapper::map($info, new OA\Info());
    }

    private function setExternalDocs(): void
    {
        $externalDocs = $this->swaggerConfig->getSwagger()['externalDocs'] ?? [];
        if ($externalDocs) {
            $this->openApi->externalDocs = Mapper::map($externalDocs, new OA\ExternalDocumentation());
        }
    }

    private function setServers(): void
    {
        $servers = $this->swaggerConfig->getSwagger()['servers'] ?? [];
        if ($servers) {
            $this->openApi->servers = Mapper::mapArray($servers, OA\Server::class);
        }
    }
}
