<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Exception;

use Hyperf\Server\Exception\RuntimeException;

class ApiDocsException extends RuntimeException
{
    public static function fileNotFound(string $path): self
    {
        return new self("File not found: {$path}");
    }

    public static function directoryCreationFailed(string $path): self
    {
        return new self("Failed to create directory: {$path}");
    }

    public static function invalidClass(string $className): self
    {
        return new self("Invalid class: {$className}");
    }

    public static function typeResolutionFailed(string $className, string $field): self
    {
        return new self("Type resolution failed for field: {$className}::{$field}");
    }
}
