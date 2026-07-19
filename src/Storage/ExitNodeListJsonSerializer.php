<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Storage;

use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\ExitNode;
use Binarylogic\TorExitNodes\ExitNodeList;
use Binarylogic\TorExitNodes\IpAddress\IpAddressNormalizer;
use JsonException;

final class ExitNodeListJsonSerializer
{
    private const EXIT_NODES_PROPERTY = 'exit_nodes';

    /**
     * @throws MalformedExitNodeListException
     */
    public function encodeExitNodeList(ExitNodeList $exitNodes): string
    {
        try {
            return json_encode(
                [self::EXIT_NODES_PROPERTY => $exitNodes->allIpAddresses()],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw MalformedExitNodeListException::forInvalidJson($exception->getMessage(), $exception);
        }
    }

    /**
     * @throws MalformedExitNodeListException
     */
    public function decodeExitNodeList(string $json): ExitNodeList
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw MalformedExitNodeListException::forInvalidJson($exception->getMessage(), $exception);
        }

        if (! is_array($decoded) || ! array_key_exists(self::EXIT_NODES_PROPERTY, $decoded)) {
            throw MalformedExitNodeListException::forMissingProperty(self::EXIT_NODES_PROPERTY);
        }

        if (! is_array($decoded[self::EXIT_NODES_PROPERTY])) {
            throw MalformedExitNodeListException::forUnexpectedPropertyType(self::EXIT_NODES_PROPERTY, 'an array');
        }

        $exitNodes = [];

        foreach ($decoded[self::EXIT_NODES_PROPERTY] as $ipAddress) {
            if (! is_string($ipAddress)) {
                throw MalformedExitNodeListException::forUnexpectedPropertyType(self::EXIT_NODES_PROPERTY, 'an array of IP address strings');
            }

            $normalized = IpAddressNormalizer::normalizeIpAddress($ipAddress);

            if ($normalized === null) {
                throw MalformedExitNodeListException::forUnexpectedPropertyType(self::EXIT_NODES_PROPERTY, 'an array of IP address strings');
            }

            $exitNodes[] = ExitNode::fromIpAddress($normalized);
        }

        return new ExitNodeList($exitNodes);
    }
}
