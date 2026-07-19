<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Checker;

use Binarylogic\TorExitNodes\Exception\InvalidIpAddressException;
use Binarylogic\TorExitNodes\ExitNode;
use Binarylogic\TorExitNodes\ExitNodeList;

final class ExitNodeChecker
{
    public function __construct(private readonly ExitNodeList $exitNodes)
    {
    }

    /**
     * @throws InvalidIpAddressException
     */
    public function isExitNode(string $ipAddress): bool
    {
        return $this->exitNodes->containsIpAddress(ExitNode::fromIpAddress($ipAddress)->ipAddress);
    }
}
