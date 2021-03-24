<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Tang\ApiDocs;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Server\Server;
use Tang\ApiDocs\Swagger\SwaggerJson;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Utils\ApplicationContext;
use Tang\ApiDocs\Swagger\SwaggerRoute;

class BootAppConfListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class
        ];
    }

    public function process(object $event)
    {

        $container = ApplicationContext::getContainer();

        $logger = $container->get(StdoutLoggerInterface::class);
        $config = $container->get(ConfigInterface::class);
        if (! $config->get('apidocs.enable',false)) {
            $logger->debug('apidocs not enable');
            return;
        }
        $outputDir = $config->get('apidocs.output_dir');
        if (! $outputDir) {
            $logger->error('/config/autoload/apidocs.php need set output_dir');
            return;
        }

        $prefix = $config->get('apidocs.prefix','/swagger');
        $swaggerRoute = new SwaggerRoute($prefix,$outputDir);

        $servers = $config->get('server.servers');
        $mark = true;
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

            $outputFileArr[] = $swagger->save();

            if($mark && $server['type'] == Server::SERVER_HTTP){

                $swaggerRoute->add($router,$server['name']);
                foreach ($outputFileArr as $outputFile) {
                    $swaggerRoute->addJson($router,$server['name'],$outputFile);
                }
//                $swaggerRoute->discard($router);
                $mark = false;
                $logger->info('Swagger Url at '.$server['host'].':'.$server['port'].$prefix);
            }
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
