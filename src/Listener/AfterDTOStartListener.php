<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Listener;

use Hyperf\DTO\Event\AfterDTOStart;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\HttpServer\Router\RouteCollector;
use Hyperf\Server\Server;
use Hyperf\Utils\ApplicationContext;
use Hyperf\ApiDocs\Swagger\SwaggerJson;
use Hyperf\ApiDocs\Swagger\SwaggerRoute;

class AfterDTOStartListener implements ListenerInterface
{

    public function listen(): array
    {
        return [
            AfterDTOStart::class,
        ];
    }

    /**
     * @param AfterDTOStart $event
     */
    public function process(object $event)
    {
        $container = ApplicationContext::getContainer();
        $logger = $container->get(StdoutLoggerInterface::class);
        $config = $container->get(ConfigInterface::class);
        $server = $event->serverConfig;
        $router = $event->router;

        if (!$config->get('apidocs.enable', false)) {
            return;
        }
        $outputDir = $config->get('apidocs.output_dir');
        if (!$outputDir) {
            return;
        }
        $swagger = new SwaggerJson($server['name']);
        foreach ($router->getData() ?? [] as $routeData) {
            foreach ($routeData ?? [] as $methods => $handlerArr) {
                array_walk_recursive($handlerArr, function ($item) use ($swagger, $methods) {
                    if ($item instanceof Handler && !($item->callback instanceof \Closure)) {
                        $prepareHandler = $this->prepareHandler($item->callback);
                        if (count($prepareHandler) > 1) {
                            [$controller, $action] = $prepareHandler;
                            $swagger->addPath($methods, $item->route, $controller, $action);
                        }
                    }
                });
            }
        }
        $swagger->save();
        $logger->debug('swagger server:[' . $server['name'] . '] file has been generated');
    }

    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new \RuntimeException('Handler not exist.');
    }
}
