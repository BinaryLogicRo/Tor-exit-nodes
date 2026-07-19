<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Parser;

use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\ExitNode;
use Binarylogic\TorExitNodes\ExitNodeList;
use Binarylogic\TorExitNodes\IpAddress\IpAddressNormalizer;
use JsonException;

final class OnionooExitNodeParser
{
    private const RELAYS_PROPERTY = 'relays';

    private const SOCKET_ADDRESS_PROPERTY = 'or_addresses';

    private const IP_ADDRESS_PROPERTY = 'exit_addresses';

    /**
     * @throws MalformedExitNodeListException
     */
    public function parseExitNodeList(string $json): ExitNodeList
    {
        $relays = $this->extractRelays($json);
        $exitNodes = [];

        foreach ($relays as $relay) {
            if (! is_array($relay)) {
                continue;
            }

            foreach ($this->extractIpAddresses($relay) as $ipAddress) {
                $exitNodes[] = ExitNode::fromIpAddress($ipAddress);
            }
        }

        return new ExitNodeList($exitNodes);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws MalformedExitNodeListException
     */
    private function extractRelays(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw MalformedExitNodeListException::forInvalidJson($exception->getMessage(), $exception);
        }

        if (! is_array($decoded) || ! array_key_exists(self::RELAYS_PROPERTY, $decoded)) {
            throw MalformedExitNodeListException::forMissingProperty(self::RELAYS_PROPERTY);
        }

        if (! is_array($decoded[self::RELAYS_PROPERTY])) {
            throw MalformedExitNodeListException::forUnexpectedPropertyType(self::RELAYS_PROPERTY, 'an array');
        }

        return $decoded[self::RELAYS_PROPERTY];
    }

    /**
     * @param array<array-key, mixed> $relay
     *
     * @return list<string>
     */
    private function extractIpAddresses(array $relay): array
    {
        $ipAddresses = [];

        foreach ($this->propertyValues($relay, self::IP_ADDRESS_PROPERTY) as $value) {
            $normalized = IpAddressNormalizer::normalizeIpAddress($value);

            if ($normalized !== null) {
                $ipAddresses[] = $normalized;
            }
        }

        foreach ($this->propertyValues($relay, self::SOCKET_ADDRESS_PROPERTY) as $value) {
            $normalized = IpAddressNormalizer::normalizeSocketAddress($value);

            if ($normalized !== null) {
                $ipAddresses[] = $normalized;
            }
        }

        return array_values(array_unique($ipAddresses));
    }

    /**
     * @param array<array-key, mixed> $relay
     *
     * @return list<string>
     */
    private function propertyValues(array $relay, string $property): array
    {
        if (! isset($relay[$property]) || ! is_array($relay[$property])) {
            return [];
        }

        return array_values(array_filter($relay[$property], is_string(...)));
    }
}
