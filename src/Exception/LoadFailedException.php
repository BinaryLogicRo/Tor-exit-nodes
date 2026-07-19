<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Exception;

use RuntimeException;
use Throwable;

final class LoadFailedException extends RuntimeException implements TorExitNodeException
{
    public static function forMissingFile(string $path): self
    {
        return new self(sprintf('Failed to load the exit node list from "%s": the file does not exist.', $path));
    }

    public static function forUnreadableFile(string $path, string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Failed to load the exit node list from "%s": %s', $path, $reason), 0, $previous);
    }

    public static function forMalformedFile(string $path, Throwable $previous): self
    {
        return new self(sprintf('Failed to load the exit node list from "%s": %s', $path, $previous->getMessage()), 0, $previous);
    }
}
