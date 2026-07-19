<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Http;

use Binarylogic\TorExitNodes\Exception\DownloadFailedException;

interface HttpClient
{
    /**
     * @throws DownloadFailedException
     */
    public function fetchBody(string $url): string;
}
