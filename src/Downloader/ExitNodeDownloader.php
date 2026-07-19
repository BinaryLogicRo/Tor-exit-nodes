<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Downloader;

use Binarylogic\TorExitNodes\Exception\DownloadFailedException;
use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\ExitNodeList;
use Binarylogic\TorExitNodes\Http\HttpClient;
use Binarylogic\TorExitNodes\Parser\OnionooExitNodeParser;

final class ExitNodeDownloader
{
    public const ONIONOO_ENDPOINT = 'https://onionoo.torproject.org/details?type=relay&running=true&flag=Exit&fields=or_addresses,exit_addresses';

    private readonly OnionooExitNodeParser $parser;

    public function __construct(
        private readonly HttpClient $httpClient,
        ?OnionooExitNodeParser $parser = null,
        private readonly string $endpoint = self::ONIONOO_ENDPOINT,
    ) {
        $this->parser = $parser ?? new OnionooExitNodeParser();
    }

    /**
     * @throws DownloadFailedException
     * @throws MalformedExitNodeListException
     */
    public function downloadExitNodes(): ExitNodeList
    {
        return $this->parser->parseExitNodeList($this->fetchRawList());
    }

    /**
     * @throws DownloadFailedException
     */
    public function fetchRawList(): string
    {
        return $this->httpClient->fetchBody($this->endpoint);
    }
}
