<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Http;

use Binarylogic\TorExitNodes\Exception\DownloadFailedException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

final class GuzzleHttpClient implements HttpClient
{
    public const DEFAULT_TIMEOUT_SECONDS = 30.0;

    public const DEFAULT_USER_AGENT = 'binarylogic/tor-exit-nodes';

    private readonly ClientInterface $client;

    public function __construct(
        ?ClientInterface $client = null,
        private readonly float $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        private readonly string $userAgent = self::DEFAULT_USER_AGENT,
    ) {
        $this->client = $client ?? new Client();
    }

    public function fetchBody(string $url): string
    {
        try {
            $response = $this->client->request('GET', $url, [
                RequestOptions::CONNECT_TIMEOUT => $this->timeoutSeconds,
                RequestOptions::TIMEOUT => $this->timeoutSeconds,
                RequestOptions::VERIFY => true,
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw DownloadFailedException::forUrl($url, $exception->getMessage(), $exception);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw DownloadFailedException::forUnexpectedStatusCode($url, $statusCode);
        }

        $body = (string) $response->getBody();

        if (trim($body) === '') {
            throw DownloadFailedException::forEmptyBody($url);
        }

        return $body;
    }
}
