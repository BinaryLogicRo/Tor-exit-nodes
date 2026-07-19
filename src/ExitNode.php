<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes;

use Binarylogic\TorExitNodes\Exception\InvalidIpAddressException;
use Binarylogic\TorExitNodes\IpAddress\IpAddressNormalizer;
use Stringable;

final class ExitNode implements Stringable
{
    private function __construct(public readonly string $ipAddress)
    {
    }

    /**
     * @throws InvalidIpAddressException
     */
    public static function fromIpAddress(string $ipAddress): self
    {
        $normalized = IpAddressNormalizer::normalizeIpAddress($ipAddress);

        if ($normalized === null) {
            throw InvalidIpAddressException::forValue($ipAddress);
        }

        return new self($normalized);
    }

    /**
     * Accepts an address that may carry a port, such as "10.0.0.1:9001" or "[2001:db8::1]:9001".
     *
     * @throws InvalidIpAddressException
     */
    public static function fromSocketAddress(string $socketAddress): self
    {
        $normalized = IpAddressNormalizer::normalizeSocketAddress($socketAddress);

        if ($normalized === null) {
            throw InvalidIpAddressException::forValue($socketAddress);
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->ipAddress === $other->ipAddress;
    }

    public function __toString(): string
    {
        return $this->ipAddress;
    }
}
