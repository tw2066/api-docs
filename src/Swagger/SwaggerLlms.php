<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Exception\ApiDocsException;
use Symfony\Component\Yaml\Yaml;

class SwaggerLlms
{
    /**
     * 支持的 HTTP 方法, 用于过滤 PathItem 中的非操作字段(summary/parameters/servers 等).
     */
    protected const HTTP_METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    /**
     * 已解析的 OpenAPI 文件缓存: filePath => ['mtime' => int, 'data' => array].
     */
    protected static array $fileCache = [];

    public function list(string $httpName, string $filePath, string $prefix): string
    {
        $openapi = $this->load($filePath);
        $info = $openapi['info'] ?? [];

        $content = sprintf("# %s\n\n", $info['title'] ?? 'API');
        $description = $this->oneLine($info['description'] ?? '');
        if ($description !== '') {
            $content .= sprintf("> %s\n\n", $description);
        }

        $groups = [];
        foreach ($openapi['paths'] ?? [] as $route => $pathItem) {
            foreach (self::HTTP_METHODS as $method) {
                $operation = $pathItem[$method] ?? null;
                if (! is_array($operation)) {
                    continue;
                }
                $tag = ! empty($operation['tags']) ? $operation['tags'][0] : 'default';
                $operationId = $operation['operationId'] ?? '';
                $name = $this->oneLine($operation['summary'] ?? '') ?: $operationId;
                $url = sprintf('%s/%s/%s.md', $prefix, $httpName, $operationId);
                $line = sprintf('- %s %s — [%s](%s)', strtoupper($method), $route, $name, $url);
                $operationDescription = $this->oneLine($operation['description'] ?? '');
                if ($operationDescription !== '') {
                    $line .= ': ' . $operationDescription;
                }
                $groups[$tag][] = $line;
            }
        }

        foreach ($groups as $tag => $lines) {
            $content .= sprintf("## %s\n\n%s\n\n", $tag, implode("\n", $lines));
        }
        return $content;
    }

    public function detail(string $operationId, string $filePath): string
    {
        // 客户端可能对 URL 中的 : + 等字符做百分号编码
        $operationId = rawurldecode($operationId);
        $openapi = $this->load($filePath);
        $route = $methods = null;
        $path = [];
        foreach ($openapi['paths'] ?? [] as $routeKey => $pathItem) {
            foreach (self::HTTP_METHODS as $method) {
                $operation = $pathItem[$method] ?? null;
                if (is_array($operation) && ($operation['operationId'] ?? null) === $operationId) {
                    [$route, $methods, $path] = [$routeKey, $method, $operation];
                    break 2;
                }
            }
        }
        if ($route === null) {
            throw ApiDocsException::operationNotFound($operationId);
        }

        $components = $openapi['components'] ?? [];
        $schemas = $this->getSchemas($path, $components);
        $usedComponents = [];
        foreach ($schemas as $schema) {
            if (isset($components['schemas'][$schema])) {
                $usedComponents['schemas'][$schema] = $components['schemas'][$schema];
            }
        }
        // 保留 securitySchemes, 否则 operation 上的 security 引用在文档里无法解析
        if (! empty($components['securitySchemes'])) {
            $usedComponents['securitySchemes'] = $components['securitySchemes'];
        }

        $openapi['paths'] = [$route => [$methods => $path]];
        if ($usedComponents) {
            $openapi['components'] = $usedComponents;
        } else {
            unset($openapi['components']);
        }
        unset($openapi['tags'], $openapi['externalDocs']);

        $content = sprintf("# %s\n\n`%s %s`\n\n", $path['summary'] ?? $path['operationId'], strtoupper($methods), $route);
        if (! empty($path['description'])) {
            $content .= sprintf("## %s\n\n", $path['description']);
        }
        $yaml = $this->dumpYaml($openapi);
        $yaml = sprintf("```yaml\n%s\n```", $yaml);
        return $content . $yaml;
    }

    protected function load(string $filePath): array
    {
        $mtime = (int) @filemtime($filePath);
        $cached = self::$fileCache[$filePath] ?? null;
        if ($cached !== null && $cached['mtime'] === $mtime) {
            return $cached['data'];
        }
        $data = json_decode((string) file_get_contents($filePath), true);
        $data = is_array($data) ? $data : [];
        self::$fileCache[$filePath] = ['mtime' => $mtime, 'data' => $data];
        return $data;
    }

    protected function dumpYaml(array $openapi): string
    {
        return Yaml::dump($openapi, 10, 2, Yaml::DUMP_OBJECT_AS_MAP);
    }

    protected function oneLine(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    protected function getSchemas(array $data, array $components, array &$visited = []): array
    {
        $schemas = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $schemas = array_merge($schemas, $this->getSchemas($item, $components, $visited));
                continue;
            }
            if (! is_string($item) || ! str_starts_with($item, '#/components/schemas/')) {
                continue;
            }
            $name = substr($item, strlen('#/components/schemas/'));
            if (isset($visited[$name])) {
                continue;
            }
            $visited[$name] = true;
            $schemas[] = $name;
            if (isset($components['schemas'][$name])) {
                $schemas = array_merge($schemas, $this->getSchemas($components['schemas'][$name], $components, $visited));
            }
        }
        return $schemas;
    }
}
