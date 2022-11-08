<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Listener;

use Closure;
use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Swagger\SwaggerComponents;
use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\ApiDocs\Swagger\SwaggerOpenApi;
use Hyperf\ApiDocs\Swagger\SwaggerPaths;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\DTO\Event\AfterDtoStart;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Router\Handler;
use RuntimeException;

class AfterDtoStartListener implements ListenerInterface
{
    public function __construct(
        private StdoutLoggerInterface $logger,
        private SwaggerOpenApi $swaggerOpenApi,
        private SwaggerComponents $swaggerComponents,
        private SwaggerConfig $swaggerConfig,
    ) {
    }

    public function listen(): array
    {
        return [
            AfterDtoStart::class,
        ];
    }

    /**
     * @param AfterDtoStart $event
     */
    public function process(object $event): void
    {
        $server = $event->serverConfig;
        $router = $event->router;

        if (! $this->swaggerConfig->isEnable()) {
            return;
        }
        if (! $this->swaggerConfig->getOutputDir()) {
            return;
        }

        $this->swaggerOpenApi->init();

        /** @var SwaggerPaths $swagger */
        $swagger = make(SwaggerPaths::class, [$server['name']]);
        foreach ($router->getData() ?? [] as $routeData) {
            foreach ($routeData ?? [] as $methods => $handlerArr) {
                array_walk_recursive($handlerArr, function ($item) use ($swagger, $methods) {
                    if ($item instanceof Handler && ! ($item->callback instanceof Closure)) {
                        $prepareHandler = $this->prepareHandler($item->callback);
                        if (count($prepareHandler) > 1) {
                            [$controller, $methodName] = $prepareHandler;
                            $swagger->addPath($controller, $methodName, $item->route, $methods);
                        }
                    }
                });
            }
        }
        // 收集swaggerComponents Schemas
        $apiModels = AnnotationCollector::getClassesByAnnotation(ApiModel::class);
        array_map(function ($className) {
            $this->swaggerComponents->generateSchemas($className);
        }, array_keys($apiModels));

        $schemas = $this->swaggerComponents->getSchemas();
        $this->swaggerOpenApi->setComponentsSchemas($schemas);
        $this->swaggerOpenApi->save($server['name']);

        $this->swaggerOpenApi->clean();
        $this->swaggerComponents->setSchemas([]);

        $this->logger->debug('swagger server:[' . $server['name'] . '] file has been generated');
    }

    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new RuntimeException('Handler not exist.');
    }
}
