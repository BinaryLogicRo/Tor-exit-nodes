<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Binarylogic\TorExitNodes\Checker\ExitNodeChecker;
use Binarylogic\TorExitNodes\Exception\InvalidIpAddressException;
use Binarylogic\TorExitNodes\ExitNodeList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExitNodeChecker::class)]
final class ExitNodeCheckerTest extends TestCase
{
    public function test_confirms_an_ip_address_that_is_an_exit_node(): void
    {
        $checker = new ExitNodeChecker(ExitNodeList::fromIpAddresses(['185.220.101.1']));

        self::assertTrue($checker->isExitNode('185.220.101.1'));
    }

    public function test_denies_an_ip_address_that_is_not_an_exit_node(): void
    {
        $checker = new ExitNodeChecker(ExitNodeList::fromIpAddresses(['185.220.101.1']));

        self::assertFalse($checker->isExitNode('8.8.8.8'));
    }

    public function test_matches_an_ipv6_exit_node_written_in_another_notation(): void
    {
        $checker = new ExitNodeChecker(ExitNodeList::fromIpAddresses(['2001:DB8::1']));

        self::assertTrue($checker->isExitNode('2001:0db8:0000:0000:0000:0000:0000:0001'));
    }

    public function test_denies_every_ip_address_when_the_list_is_empty(): void
    {
        $checker = new ExitNodeChecker(new ExitNodeList());

        self::assertFalse($checker->isExitNode('185.220.101.1'));
    }

    public function test_rejects_an_ip_address_that_is_not_valid(): void
    {
        $checker = new ExitNodeChecker(ExitNodeList::fromIpAddresses(['185.220.101.1']));

        $this->expectException(InvalidIpAddressException::class);
        $this->expectExceptionMessage('"not-an-ip" is not a valid IP address.');

        $checker->isExitNode('not-an-ip');
    }

    public function test_rejects_an_ip_address_that_carries_a_port(): void
    {
        $checker = new ExitNodeChecker(ExitNodeList::fromIpAddresses(['185.220.101.1']));

        $this->expectException(InvalidIpAddressException::class);

        $checker->isExitNode('185.220.101.1:9001');
    }
}
