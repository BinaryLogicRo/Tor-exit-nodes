<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\IpAddress;

/**
 * @internal
 */
final class IpAddressNormalizer
{
    public static function normalizeIpAddress(string $ipAddress): ?string
    {
        $ipAddress = trim($ipAddress);

        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($ipAddress);

        if ($packed === false) {
            return null;
        }

        $canonical = inet_ntop($packed);

        return $canonical === false ? null : strtolower($canonical);
    }

    public static function normalizeSocketAddress(string $socketAddress): ?string
    {
        return self::normalizeIpAddress(self::stripPort(trim($socketAddress)));
    }

    private static function stripPort(string $socketAddress): string
    {
        if (preg_match('/^\[(?<address>.+)\](?::\d+)?$/', $socketAddress, $matches) === 1) {
            return $matches['address'];
        }

        if (substr_count($socketAddress, ':') === 1) {
            return strstr($socketAddress, ':', true) ?: $socketAddress;
        }

        return $socketAddress;
    }
}
