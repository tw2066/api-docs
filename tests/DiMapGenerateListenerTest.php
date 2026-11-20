<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Listener\DiMapGenerateListener;
use Hyperf\ApiDocs\Swagger\SwaggerConfig;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DTO\DtoConfig;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class DiMapGenerateListenerTest extends TestCase
{
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        m::close();
        foreach ($this->tempDirs as $dir) {
            foreach (glob($dir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
        $this->tempDirs = [];
    }

    public function testGenerateWritesValidJsonWithoutTmpLeftovers(): void
    {
        $dir = sys_get_temp_dir() . '/apidocs-dimap-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;

        $logger = m::mock(StdoutLoggerInterface::class);
        $logger->shouldNotReceive('error');
        $logger->shouldReceive('debug');

        $swaggerConfig = m::mock(SwaggerConfig::class);
        $swaggerConfig->shouldReceive('isEnable')->andReturn(true);
        $swaggerConfig->shouldReceive('getOutputDir')->andReturn($dir);

        $dtoConfig = m::mock(DtoConfig::class);
        $dtoConfig->shouldReceive('isScanCacheable')->andReturn(false);

        $listener = new DiMapGenerateListener(
            $logger,
            $swaggerConfig,
            m::mock(ContainerInterface::class),
            $dtoConfig
        );
        $listener->generate();

        $path = $dir . '/di-map.json';
        $this->assertFileExists($path);

        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data);
        $this->assertSame(\BASE_PATH, $data['base_path']);
        $this->assertSame([], $data['definitions']);
        $this->assertSame([], $data['resolved']);
        // 唯一临时文件已 rename，不应残留
        $this->assertSame([], glob($dir . '/di-map.json.tmp*') ?: []);
    }
}
