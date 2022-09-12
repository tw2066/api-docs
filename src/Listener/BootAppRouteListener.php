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

/**
 * 添加路由到框架.
 */
class BootAppRouteListener implements ListenerInterface
{
    public static string $massage;

    public function __construct(
        private StdoutLoggerInterface $logger,
        private ConfigInterface $config,
        private DispatcherFactory $dispatcherFactory,
    ) {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $this->config->get('api_docs.enable', false)) {
            $this->logger->debug('api_docs not enable');
            return;
        }
        $outputDir = $this->config->get('api_docs.output_dir');
        if (! $outputDir) {
            $this->logger->error('/config/autoload/api_docs.php need set output_dir');
            return;
        }
        if ($this->config->get('api_docs.validation_custom_attributes', false)) {
            ValidationDto::$isValidationCustomAttributes = true;
        }
        $prefix = $this->config->get('api_docs.prefix_url', '/swagger');
        $servers = $this->config->get('server.servers');
        $httpServerRouter = null;
        $httpServer = null;
        foreach ($servers as $server) {
            $router = $this->dispatcherFactory->getRouter($server['name']);
            if (empty($httpServerRouter) && $server['type'] == Server::SERVER_HTTP) {
                $httpServerRouter = $router;
                $httpServer = $server;
            }
        }
        if (empty($httpServerRouter)) {
            $this->logger->warning('Swagger: http Service not started');
            return;
        }
        $swaggerRoute = new SwaggerRoute($prefix, $httpServer['name']);
        $swaggerRoute->add($httpServerRouter);
        static::$massage = 'Swagger docs url at http://' . $httpServer['host'] . ':' . $httpServer['port'] . $prefix;
    }
}
