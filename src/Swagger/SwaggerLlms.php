<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\Stringable\Str;
use Symfony\Component\Yaml\Yaml;

use function Hyperf\Support\make;

class SwaggerLlms
{
    public function list(string $httpName, string $filePath, string $prefix): string
    {
        $swaggerJson = file_get_contents($filePath);
        $openapi = json_decode($swaggerJson, true);

        $content = sprintf("# %s\n\n## %s\n\n", $openapi['info']['title'], $openapi['info']['description']);

        foreach ($openapi['paths'] as $pathItem) {
            // 遍历路径下的所有请求方法（get/post/put/delete等）
            foreach ($pathItem as $operation) {
                // 提取核心字段
                $tag = ! empty($operation['tags']) ? $operation['tags'][0] : 'default';
                $summary = $operation['summary'] ?? '';
                $description = $operation['description'] ?? '';
                $operationId = $operation['operationId'] ?? '';
                $name = $summary ?: $operationId;
                $url = sprintf('%s/%s/%s.md', $prefix, $httpName, $operation['operationId']);
                $content .= "- {$tag} [{$name}]({$url}): {$description}\n";
            }
        }
        return $content;
    }

    public function detail(string $operationId, string $filePath): string
    {
        $swaggerPaths = make(SwaggerPaths::class, ['http']);
        [$route, $methods] = $swaggerPaths->getRouteByOperationId($operationId);

        $openapi = json_decode(file_get_contents($filePath), true);
        $path = $openapi['paths'][$route][$methods];
        $components = $openapi['components'];

        $schemas = $this->getSchemas($path, $components);
        $newComponents = [];
        foreach ($schemas as $schema) {
            $newComponents['components']['schemas'][$schema] = $components['schemas'][$schema];
        }
        $openapi['paths'] = [];
        $openapi['paths'][$route][$methods] = $path;
        $openapi['components'] = $newComponents['components'] ?? [];
        unset($openapi['tags'], $openapi['externalDocs']);

        $flags = Yaml::DUMP_OBJECT_AS_MAP ^ Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE;
        $yaml = Yaml::dump($openapi, 10, 2, $flags);
        $content = sprintf("# %s\n\n## %s\n\n", $path['summary'] ?? $path['operationId'], $path['description'] ?? '');
        $yaml = sprintf("```yaml\n%s\n```", $yaml);
        return $content . $yaml;
    }

    protected function getSchemas($data = [], array $components = []): array
    {
        $schemas = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $schema = $this->getSchemas($item, $components);
                if (! empty($schema)) {
                    $schemas = array_merge($schemas, $schema);
                }
            }
            if (is_string($item) && Str::startsWith($item, '#/components/schemas/')) {
                $tmp = str_replace('#/components/schemas/', '', $item);
                $schemas[] = $tmp;
                $schema = $this->getSchemas($components['schemas'][$tmp], $components);
                if (! empty($schema)) {
                    $schemas = array_merge($schemas, $schema);
                }
            }
        }
        return array_unique($schemas);
    }
}
