<?php

declare(strict_types=1);

namespace Tests\Unit\Parser;

use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\Parser\OnionooExitNodeParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OnionooExitNodeParser::class)]
final class OnionooExitNodeParserTest extends TestCase
{
    private OnionooExitNodeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new OnionooExitNodeParser();
    }

    public function test_parses_exit_addresses_and_or_addresses(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":["185.220.101.1:9001"],"exit_addresses":["203.0.113.7"]}]}');

        self::assertSame(['203.0.113.7', '185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_strips_ports_from_or_addresses(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":["185.220.101.1:9001","[2001:DB8::1]:443"]}]}');

        self::assertSame(['185.220.101.1', '2001:db8::1'], $exitNodes->allIpAddresses());
    }

    public function test_parses_a_relay_without_exit_addresses(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":["185.220.101.1:9001"]}]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_discards_addresses_repeated_across_relays(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":["185.220.101.1:9001"]},{"or_addresses":["185.220.101.1:443"]}]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_discards_an_address_listed_as_both_an_exit_and_an_or_address(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":["185.220.101.1:9001"],"exit_addresses":["185.220.101.1"]}]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_returns_an_empty_list_when_there_are_no_relays(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[]}');

        self::assertTrue($exitNodes->isEmpty());
    }

    public function test_skips_addresses_that_are_not_valid_ip_addresses(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":["not-an-ip:9001","185.220.101.1:9001"],"exit_addresses":["999.1.1.1"]}]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_skips_relays_that_are_not_objects(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":["nonsense",42,null,{"or_addresses":["185.220.101.1:9001"]}]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_skips_address_properties_that_are_not_arrays(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":"185.220.101.1:9001","exit_addresses":null}]}');

        self::assertTrue($exitNodes->isEmpty());
    }

    public function test_skips_address_entries_that_are_not_strings(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"relays":[{"or_addresses":[42,{"a":"b"},"185.220.101.1:9001"]}]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    public function test_ignores_unknown_properties(): void
    {
        $exitNodes = $this->parser->parseExitNodeList('{"version":"10.0","relays":[{"nickname":"relay","or_addresses":["185.220.101.1:9001"]}],"bridges":[]}');

        self::assertSame(['185.220.101.1'], $exitNodes->allIpAddresses());
    }

    #[DataProvider('malformedPayloads')]
    public function test_rejects_a_malformed_payload(string $payload, string $expectedMessage): void
    {
        $this->expectException(MalformedExitNodeListException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->parser->parseExitNodeList($payload);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function malformedPayloads(): iterable
    {
        yield 'empty input' => ['', 'is not valid JSON'];
        yield 'broken json' => ['{"relays":', 'is not valid JSON'];
        yield 'not json at all' => ['<html></html>', 'is not valid JSON'];
        yield 'json scalar' => ['"a string"', 'does not contain the expected "relays" property'];
        yield 'missing relays property' => ['{"version":"10.0"}', 'does not contain the expected "relays" property'];
        yield 'relays is not an array' => ['{"relays":"nonsense"}', 'expected to be an array'];
    }
}
