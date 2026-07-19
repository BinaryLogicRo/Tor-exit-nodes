<?php

declare(strict_types=1);

namespace Tests\Unit;

use Binarylogic\TorExitNodes\Exception\InvalidIpAddressException;
use Binarylogic\TorExitNodes\ExitNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExitNode::class)]
final class ExitNodeTest extends TestCase
{
    public function test_creates_an_exit_node_from_an_ipv4_address(): void
    {
        $exitNode = ExitNode::fromIpAddress('185.220.101.1');

        self::assertSame('185.220.101.1', $exitNode->ipAddress);
    }

    public function test_canonicalises_an_ipv6_address(): void
    {
        $exitNode = ExitNode::fromIpAddress('2001:0DB8:0000:0000:0000:0000:0000:0001');

        self::assertSame('2001:db8::1', $exitNode->ipAddress);
    }

    public function test_strips_the_port_from_an_ipv4_socket_address(): void
    {
        $exitNode = ExitNode::fromSocketAddress('185.220.101.1:9001');

        self::assertSame('185.220.101.1', $exitNode->ipAddress);
    }

    public function test_strips_the_brackets_and_port_from_an_ipv6_socket_address(): void
    {
        $exitNode = ExitNode::fromSocketAddress('[2001:db8::1]:9001');

        self::assertSame('2001:db8::1', $exitNode->ipAddress);
    }

    public function test_rejects_an_ip_address_that_is_not_valid(): void
    {
        $this->expectException(InvalidIpAddressException::class);
        $this->expectExceptionMessage('"999.1.1.1" is not a valid IP address.');

        ExitNode::fromIpAddress('999.1.1.1');
    }

    public function test_rejects_an_ip_address_that_carries_a_port(): void
    {
        $this->expectException(InvalidIpAddressException::class);

        ExitNode::fromIpAddress('185.220.101.1:9001');
    }

    public function test_rejects_a_socket_address_that_is_not_valid(): void
    {
        $this->expectException(InvalidIpAddressException::class);

        ExitNode::fromSocketAddress('not-an-ip:9001');
    }

    public function test_considers_two_nodes_with_the_same_address_equal(): void
    {
        $exitNode = ExitNode::fromIpAddress('2001:db8::1');

        self::assertTrue($exitNode->equals(ExitNode::fromIpAddress('2001:DB8:0:0:0:0:0:1')));
        self::assertFalse($exitNode->equals(ExitNode::fromIpAddress('2001:db8::2')));
    }

    public function test_casts_to_its_ip_address(): void
    {
        self::assertSame('185.220.101.1', (string) ExitNode::fromIpAddress('185.220.101.1'));
    }
}
