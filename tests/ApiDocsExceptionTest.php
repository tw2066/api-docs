<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use Hyperf\ApiDocs\Exception\ApiDocsException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ApiDocsExceptionTest extends TestCase
{
    public function testFileNotFoundException(): void
    {
        $exception = ApiDocsException::fileNotFound('/path/to/file.json');

        $this->assertEquals('File not found: /path/to/file.json', $exception->getMessage());
    }

    public function testDirectoryCreationFailedException(): void
    {
        $exception = ApiDocsException::directoryCreationFailed('/path/to/dir');

        $this->assertEquals('Failed to create directory: /path/to/dir', $exception->getMessage());
    }

    public function testInvalidClassException(): void
    {
        $exception = ApiDocsException::invalidClass('NonExistentClass');

        $this->assertEquals('Invalid class: NonExistentClass', $exception->getMessage());
    }

    public function testTypeResolutionFailedException(): void
    {
        $exception = ApiDocsException::typeResolutionFailed('User', 'name');

        $this->assertEquals('Type resolution failed for field: User::name', $exception->getMessage());
    }
}
