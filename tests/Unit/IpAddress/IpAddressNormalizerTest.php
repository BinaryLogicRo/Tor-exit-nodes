<?php

declare(strict_types=1);

namespace Tests\Unit\IpAddress;

use Binarylogic\TorExitNodes\IpAddress\IpAddressNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IpAddressNormalizer::class)]
final class IpAddressNormalizerTest extends TestCase
{
    public function test_keeps_an_ipv4_address_unchanged(): void
    {
        self::assertSame('185.220.101.1', IpAddressNormalizer::normalizeIpAddress('185.220.101.1'));
    }

    public function test_compresses_and_lowercases_an_ipv6_address(): void
    {
        self::assertSame('2001:db8::1', IpAddressNormalizer::normalizeIpAddress('2001:0DB8:0000:0000:0000:0000:0000:0001'));
    }

    public function test_trims_surrounding_whitespace(): void
    {
        self::assertSame('185.220.101.1', IpAddressNormalizer::normalizeIpAddress("  185.220.101.1\n"));
    }

    #[DataProvider('invalidIpAddresses')]
    public function test_returns_null_for_an_invalid_ip_address(string $value): void
    {
        self::assertNull(IpAddressNormalizer::normalizeIpAddress($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIpAddresses(): iterable
    {
        yield 'empty string' => [''];
        yield 'text' => ['not-an-ip'];
        yield 'out of range octet' => ['999.1.1.1'];
        yield 'truncated address' => ['185.220.101'];
        yield 'address with a port' => ['185.220.101.1:9001'];
        yield 'malformed ipv6' => ['2001:db8:::1'];
    }

    public function test_strips_the_port_from_an_ipv4_socket_address(): void
    {
        self::assertSame('185.220.101.1', IpAddressNormalizer::normalizeSocketAddress('185.220.101.1:9001'));
    }

    public function test_strips_the_brackets_and_port_from_an_ipv6_socket_address(): void
    {
        self::assertSame('2001:db8::1', IpAddressNormalizer::normalizeSocketAddress('[2001:DB8::1]:9001'));
    }

    public function test_strips_the_brackets_from_an_ipv6_address_without_a_port(): void
    {
        self::assertSame('2001:db8::1', IpAddressNormalizer::normalizeSocketAddress('[2001:db8::1]'));
    }

    public function test_keeps_a_bare_ipv6_address_intact(): void
    {
        self::assertSame('2001:db8::1', IpAddressNormalizer::normalizeSocketAddress('2001:db8::1'));
    }

    public function test_accepts_a_socket_address_without_a_port(): void
    {
        self::assertSame('185.220.101.1', IpAddressNormalizer::normalizeSocketAddress('185.220.101.1'));
    }

    #[DataProvider('invalidSocketAddresses')]
    public function test_returns_null_for_an_invalid_socket_address(string $value): void
    {
        self::assertNull(IpAddressNormalizer::normalizeSocketAddress($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSocketAddresses(): iterable
    {
        yield 'empty string' => [''];
        yield 'text with a port' => ['not-an-ip:9001'];
        yield 'port only' => [':9001'];
        yield 'unbracketed ipv6 with a port' => ['2001:db8::1:9001:extra'];
        yield 'empty brackets' => ['[]:9001'];
    }
}
