<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Listener;

use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\ApiDocs\Swagger\SwaggerController;
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

    public static string $httpServerName;

    public function __construct(
        private StdoutLoggerInterface $logger,
        private ConfigInterface $config,
        private DispatcherFactory $dispatcherFactory,
        private SwaggerConfig $swaggerConfig,
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
        // 提前设置
        if ($this->swaggerConfig->isValidationCustomAttributes()) {
            ValidationDto::$isValidationCustomAttributes = true;
        }

        if (! $this->swaggerConfig->isEnable()) {
            $this->logger->debug('api_docs swagger not enable');
            return;
        }
        if (! $this->swaggerConfig->getOutputDir()) {
            $this->logger->error('/config/autoload/api_docs.php need set output_dir');
            return;
        }
        $prefix = $this->swaggerConfig->getPrefixUrl();
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
        // 添加路由
        $httpServerRouter->addGroup($prefix, function ($route) {
            $route->get('', [SwaggerController::class, 'index']);
            $route->get('/{httpName}.json', [SwaggerController::class, 'getJsonFile']);
            $route->get('/{httpName}.yaml', [SwaggerController::class, 'getYamlFile']);
            $route->get('/{file}', [SwaggerController::class, 'getFile']);
        });
        self::$httpServerName = $httpServer['name'];

        static::$massage = 'Swagger docs url at http://' . $httpServer['host'] . ':' . $httpServer['port'] . $prefix;
    }
}
