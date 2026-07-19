<?php

declare(strict_types=1);

namespace Tests\Unit;

use Binarylogic\TorExitNodes\Exception\InvalidIpAddressException;
use Binarylogic\TorExitNodes\ExitNode;
use Binarylogic\TorExitNodes\ExitNodeList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExitNodeList::class)]
final class ExitNodeListTest extends TestCase
{
    public function test_contains_an_ip_address_it_was_built_with(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1']);

        self::assertTrue($exitNodes->containsIpAddress('185.220.101.1'));
        self::assertTrue($exitNodes->containsIpAddress('2001:db8::1'));
    }

    public function test_matches_an_ipv6_address_written_in_another_notation(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['2001:db8::1']);

        self::assertTrue($exitNodes->containsIpAddress('2001:0DB8:0000:0000:0000:0000:0000:0001'));
    }

    public function test_does_not_contain_an_unknown_ip_address(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1']);

        self::assertFalse($exitNodes->containsIpAddress('8.8.8.8'));
    }

    public function test_does_not_contain_an_invalid_ip_address(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1']);

        self::assertFalse($exitNodes->containsIpAddress('not-an-ip'));
    }

    public function test_rejects_an_invalid_ip_address_when_building_from_strings(): void
    {
        $this->expectException(InvalidIpAddressException::class);

        ExitNodeList::fromIpAddresses(['185.220.101.1', 'not-an-ip']);
    }

    public function test_discards_duplicate_exit_nodes(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1', '185.220.101.1', '2001:DB8::1', '2001:db8::1']);

        self::assertCount(2, $exitNodes);
        self::assertSame(['185.220.101.1', '2001:db8::1'], $exitNodes->allIpAddresses());
    }

    public function test_returns_a_new_instance_when_an_exit_node_is_added(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1']);

        $extended = $exitNodes->withExitNode(ExitNode::fromIpAddress('8.8.8.8'));

        self::assertNotSame($exitNodes, $extended);
        self::assertCount(1, $exitNodes);
        self::assertCount(2, $extended);
        self::assertFalse($exitNodes->containsIpAddress('8.8.8.8'));
        self::assertTrue($extended->containsIpAddress('8.8.8.8'));
    }

    public function test_is_empty_when_built_without_exit_nodes(): void
    {
        $exitNodes = new ExitNodeList();

        self::assertTrue($exitNodes->isEmpty());
        self::assertCount(0, $exitNodes);
        self::assertSame([], $exitNodes->all());
        self::assertSame([], $exitNodes->allIpAddresses());
        self::assertFalse($exitNodes->containsIpAddress('185.220.101.1'));
    }

    public function test_iterates_over_its_exit_nodes_keyed_by_ip_address(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1']);

        $iterated = [];

        foreach ($exitNodes as $ipAddress => $exitNode) {
            $iterated[$ipAddress] = $exitNode->ipAddress;
        }

        self::assertSame([
            '185.220.101.1' => '185.220.101.1',
            '2001:db8::1' => '2001:db8::1',
        ], $iterated);
    }

    public function test_returns_all_of_its_exit_nodes(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1']);

        $all = $exitNodes->all();

        self::assertCount(1, $all);
        self::assertInstanceOf(ExitNode::class, $all[0]);
        self::assertSame('185.220.101.1', $all[0]->ipAddress);
    }
}
