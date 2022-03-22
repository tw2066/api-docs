<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\Di\Annotation\AnnotationCollector;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SwaggerCommonTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }

    public function testScan()
    {
        $this->assertTrue(true);
    }
}
