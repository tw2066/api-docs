<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Listener;

use Hyperf\ApiDocs\Swagger\SwaggerRoute;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DTO\ValidationDto;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Server\Server;
use Hyperf\Utils\ApplicationContext;

/**
 * 添加路由到框架.
 */
class BootAppRouteListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $container = ApplicationContext::getContainer();
        $logger = $container->get(StdoutLoggerInterface::class);
        $config = $container->get(ConfigInterface::class);
        if (! $config->get('api_docs.enable', false)) {
            $logger->debug('api_docs not enable');
            return;
        }
        $outputDir = $config->get('api_docs.output_dir');
        if (! $outputDir) {
            $logger->error('/config/autoload/api_docs.php need set output_dir');
            return;
        }
        if ($config->get('api_docs.validation_custom_attributes', false)) {
            ValidationDto::$isValidationCustomAttributes = true;
        }
        $prefix = $config->get('api_docs.prefix_url', '/swagger');
        $servers = $config->get('server.servers');
        $httpServerRouter = null;
        $httpServer = null;
        foreach ($servers as $server) {
            $router = $container->get(DispatcherFactory::class)->getRouter($server['name']);
            if (empty($httpServerRouter) && $server['type'] == Server::SERVER_HTTP) {
                $httpServerRouter = $router;
                $httpServer = $server;
            }
        }
        if (empty($httpServerRouter)) {
            $logger->warning('Swagger: http Service not started');
            return;
        }
        $swaggerRoute = new SwaggerRoute($prefix, $httpServer['name']);
        $swaggerRoute->add($httpServerRouter);
        $logger->info('Swagger Url at ' . $httpServer['host'] . ':' . $httpServer['port'] . $prefix);
    }
}
