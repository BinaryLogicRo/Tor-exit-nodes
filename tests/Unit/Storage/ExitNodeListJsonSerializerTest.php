<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\ExitNodeList;
use Binarylogic\TorExitNodes\Storage\ExitNodeListJsonSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExitNodeListJsonSerializer::class)]
final class ExitNodeListJsonSerializerTest extends TestCase
{
    private ExitNodeListJsonSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new ExitNodeListJsonSerializer();
    }

    public function test_encodes_a_list_of_exit_nodes(): void
    {
        $json = $this->serializer->encodeExitNodeList(ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1']));

        self::assertSame('{"exit_nodes":["185.220.101.1","2001:db8::1"]}', $json);
    }

    public function test_encodes_an_empty_list(): void
    {
        self::assertSame('{"exit_nodes":[]}', $this->serializer->encodeExitNodeList(new ExitNodeList()));
    }

    public function test_decodes_a_list_of_exit_nodes(): void
    {
        $exitNodes = $this->serializer->decodeExitNodeList('{"exit_nodes":["185.220.101.1","2001:DB8::1"]}');

        self::assertSame(['185.220.101.1', '2001:db8::1'], $exitNodes->allIpAddresses());
    }

    public function test_decodes_an_empty_list(): void
    {
        self::assertTrue($this->serializer->decodeExitNodeList('{"exit_nodes":[]}')->isEmpty());
    }

    public function test_round_trips_a_list_of_exit_nodes(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1', '203.0.113.7']);

        $decoded = $this->serializer->decodeExitNodeList($this->serializer->encodeExitNodeList($exitNodes));

        self::assertSame($exitNodes->allIpAddresses(), $decoded->allIpAddresses());
    }

    #[DataProvider('malformedPayloads')]
    public function test_rejects_a_malformed_payload(string $payload, string $expectedMessage): void
    {
        $this->expectException(MalformedExitNodeListException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->serializer->decodeExitNodeList($payload);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function malformedPayloads(): iterable
    {
        yield 'empty input' => ['', 'is not valid JSON'];
        yield 'broken json' => ['{"exit_nodes":', 'is not valid JSON'];
        yield 'json scalar' => ['42', 'does not contain the expected "exit_nodes" property'];
        yield 'missing property' => ['{"nodes":[]}', 'does not contain the expected "exit_nodes" property'];
        yield 'property is not an array' => ['{"exit_nodes":"185.220.101.1"}', 'expected to be an array'];
        yield 'entry is not a string' => ['{"exit_nodes":[42]}', 'an array of IP address strings'];
        yield 'entry is not an ip address' => ['{"exit_nodes":["not-an-ip"]}', 'an array of IP address strings'];
        yield 'entry carries a port' => ['{"exit_nodes":["185.220.101.1:9001"]}', 'an array of IP address strings'];
    }
}
