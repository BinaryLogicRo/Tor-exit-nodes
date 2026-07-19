<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Exception;

use RuntimeException;
use Throwable;

final class SaveFailedException extends RuntimeException implements TorExitNodeException
{
    public static function forPath(string $path, string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Failed to save the exit node list to "%s": %s', $path, $reason), 0, $previous);
    }
}
