<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes;

use ArrayIterator;
use Binarylogic\TorExitNodes\Exception\InvalidIpAddressException;
use Binarylogic\TorExitNodes\IpAddress\IpAddressNormalizer;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, ExitNode>
 */
final class ExitNodeList implements Countable, IteratorAggregate
{
    /**
     * @var array<string, ExitNode>
     */
    private readonly array $exitNodes;

    /**
     * @param iterable<ExitNode> $exitNodes
     */
    public function __construct(iterable $exitNodes = [])
    {
        $indexed = [];

        foreach ($exitNodes as $exitNode) {
            $indexed[$exitNode->ipAddress] = $exitNode;
        }

        $this->exitNodes = $indexed;
    }

    /**
     * @param iterable<string> $ipAddresses
     *
     * @throws InvalidIpAddressException
     */
    public static function fromIpAddresses(iterable $ipAddresses): self
    {
        $exitNodes = [];

        foreach ($ipAddresses as $ipAddress) {
            $exitNodes[] = ExitNode::fromIpAddress($ipAddress);
        }

        return new self($exitNodes);
    }

    public function containsIpAddress(string $ipAddress): bool
    {
        $normalized = IpAddressNormalizer::normalizeIpAddress($ipAddress);

        return $normalized !== null && isset($this->exitNodes[$normalized]);
    }

    public function withExitNode(ExitNode $exitNode): self
    {
        return new self([...array_values($this->exitNodes), $exitNode]);
    }

    /**
     * @return list<ExitNode>
     */
    public function all(): array
    {
        return array_values($this->exitNodes);
    }

    /**
     * @return list<string>
     */
    public function allIpAddresses(): array
    {
        return array_keys($this->exitNodes);
    }

    public function isEmpty(): bool
    {
        return $this->exitNodes === [];
    }

    public function count(): int
    {
        return count($this->exitNodes);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->exitNodes);
    }
}
