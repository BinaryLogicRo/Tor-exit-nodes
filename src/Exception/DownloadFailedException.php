<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Exception;

use RuntimeException;
use Throwable;

final class DownloadFailedException extends RuntimeException implements TorExitNodeException
{
    public static function forUrl(string $url, string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Failed to download the exit node list from "%s": %s', $url, $reason), 0, $previous);
    }

    public static function forUnexpectedStatusCode(string $url, int $statusCode): self
    {
        return new self(sprintf('Failed to download the exit node list from "%s": unexpected HTTP status code %d.', $url, $statusCode));
    }

    public static function forEmptyBody(string $url): self
    {
        return new self(sprintf('Failed to download the exit node list from "%s": the response body was empty.', $url));
    }
}
