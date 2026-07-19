<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Exception;

use InvalidArgumentException;

final class InvalidIpAddressException extends InvalidArgumentException implements TorExitNodeException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid IP address.', $value));
    }
}
