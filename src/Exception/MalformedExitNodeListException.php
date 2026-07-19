<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Exception;

use RuntimeException;
use Throwable;

final class MalformedExitNodeListException extends RuntimeException implements TorExitNodeException
{
    public static function forInvalidJson(string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('The exit node list is not valid JSON: %s', $reason), 0, $previous);
    }

    public static function forMissingProperty(string $property): self
    {
        return new self(sprintf('The exit node list does not contain the expected "%s" property.', $property));
    }

    public static function forUnexpectedPropertyType(string $property, string $expectedType): self
    {
        return new self(sprintf('The "%s" property of the exit node list is expected to be %s.', $property, $expectedType));
    }
}
