<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs;

use Hyperf\DTO\Event\AfterDTOStart;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
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

    public function process(object $event)
    {
        $container = ApplicationContext::getContainer();
        $logger = $container->get(StdoutLoggerInterface::class);
        $config = $container->get(ConfigInterface::class);
        if (! $config->get('apidocs.enable', false)) {
            $logger->debug('apidocs not enable');
            return;
        }
        $outputDir = $config->get('apidocs.output_dir');
        if (! $outputDir) {
            $logger->error('/config/autoload/apidocs.php need set output_dir');
            return;
        }
        $servers = $config->get('server.servers');
        $httpServerRouter = null;
        $httpServer = null;
        $outputFileArr = [];
        foreach ($servers as $server) {
            $router = $container->get(DispatcherFactory::class)->getRouter($server['name']);
            $data = $router->getData();
            $swagger = new SwaggerJson($server['name']);
            foreach ($data ?? [] as $routeData) {
                foreach ($routeData ?? [] as $methods => $handlerArr) {
                    array_walk_recursive($handlerArr, function ($item) use ($swagger,$methods) {
                        if ($item instanceof Handler && ! ($item->callback instanceof \Closure)) {
                            [$controller, $action] = $this->prepareHandler($item->callback);
                            $swagger->addPath($methods, $item->route, $controller, $action);
                        }
                    });
                }
            }

            $outputFileArr[$server['name']] = $swagger->save();
            if (empty($httpServerRouter) && $server['type'] == Server::SERVER_HTTP) {
                $httpServerRouter = $router;
                $httpServer = $server;
            }
        }

        if(empty($httpServerRouter)){
            $logger->warning('Swagger: http Service not started');
            return;
        }

        $prefix = $config->get('apidocs.prefix', '/swagger');
        $swaggerRoute = new SwaggerRoute($prefix, $outputDir);
        $swaggerRoute->add($httpServerRouter, $httpServer['name']);
        $logger->info(sprintf('Swagger Url at %s:%s',$httpServer['host'],$httpServer['port'] . $prefix));
        foreach ($outputFileArr as $serverName=>$outputFile) {
            $swaggerRoute->addJson($httpServerRouter, $serverName, $outputFile);
        }
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
