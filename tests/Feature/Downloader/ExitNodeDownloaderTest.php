<?php

declare(strict_types=1);

namespace Tests\Feature\Downloader;

use ArrayObject;
use Binarylogic\TorExitNodes\Downloader\ExitNodeDownloader;
use Binarylogic\TorExitNodes\Exception\DownloadFailedException;
use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\Http\GuzzleHttpClient;
use Binarylogic\TorExitNodes\Http\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Tests\TestCase;

#[CoversClass(ExitNodeDownloader::class)]
final class ExitNodeDownloaderTest extends TestCase
{
    private const RELAY_RESPONSE = '{"relays":[{"or_addresses":["185.220.101.1:9001"],"exit_addresses":["203.0.113.7"]},{"or_addresses":["[2001:DB8::1]:9001"]}]}';

    /**
     * @var ArrayObject<int, array<array-key, mixed>>
     */
    private ArrayObject $transactions;

    protected function setUp(): void
    {
        $this->transactions = new ArrayObject();
    }

    public function test_downloads_and_parses_the_exit_node_list(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(200, [], self::RELAY_RESPONSE)));

        $exitNodes = $downloader->downloadExitNodes();

        self::assertSame(['203.0.113.7', '185.220.101.1', '2001:db8::1'], $exitNodes->allIpAddresses());
    }

    public function test_requests_the_onionoo_endpoint_by_default(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(200, [], self::RELAY_RESPONSE)));

        $downloader->downloadExitNodes();

        self::assertSame(ExitNodeDownloader::ONIONOO_ENDPOINT, (string) $this->requestedUri());
    }

    public function test_requests_a_configured_endpoint(): void
    {
        $downloader = new ExitNodeDownloader(
            $this->httpClientReturning(new Response(200, [], self::RELAY_RESPONSE)),
            endpoint: 'https://onionoo.example.com/details',
        );

        $downloader->downloadExitNodes();

        self::assertSame('https://onionoo.example.com/details', (string) $this->requestedUri());
    }

    public function test_returns_the_raw_list_without_parsing_it(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(200, [], self::RELAY_RESPONSE)));

        self::assertSame(self::RELAY_RESPONSE, $downloader->fetchRawList());
    }

    public function test_returns_an_empty_list_when_no_relays_are_running(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(200, [], '{"relays":[]}')));

        self::assertTrue($downloader->downloadExitNodes()->isEmpty());
    }

    public function test_accepts_any_http_client_implementation(): void
    {
        $httpClient = new class () implements HttpClient {
            public string $requestedUrl = '';

            public function fetchBody(string $url): string
            {
                $this->requestedUrl = $url;

                return '{"relays":[{"exit_addresses":["203.0.113.7"]}]}';
            }
        };

        $downloader = new ExitNodeDownloader($httpClient);

        self::assertSame(['203.0.113.7'], $downloader->downloadExitNodes()->allIpAddresses());
        self::assertSame(ExitNodeDownloader::ONIONOO_ENDPOINT, $httpClient->requestedUrl);
    }

    public function test_fails_when_the_list_cannot_be_downloaded(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(500, [], 'Server Error')));

        $this->expectException(DownloadFailedException::class);

        $downloader->downloadExitNodes();
    }

    public function test_fails_when_the_endpoint_cannot_be_reached(): void
    {
        $mockHandler = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', ExitNodeDownloader::ONIONOO_ENDPOINT)),
        ]);
        $downloader = new ExitNodeDownloader(new GuzzleHttpClient($this->guzzleClient($mockHandler)));

        $this->expectException(DownloadFailedException::class);

        $downloader->downloadExitNodes();
    }

    public function test_fails_when_the_raw_list_cannot_be_downloaded(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(404, [], 'Not Found')));

        $this->expectException(DownloadFailedException::class);

        $downloader->fetchRawList();
    }

    public function test_fails_when_the_response_is_not_a_valid_exit_node_list(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(200, [], '<html>maintenance</html>')));

        $this->expectException(MalformedExitNodeListException::class);

        $downloader->downloadExitNodes();
    }

    public function test_fails_when_the_response_has_no_relays_property(): void
    {
        $downloader = new ExitNodeDownloader($this->httpClientReturning(new Response(200, [], '{"version":"10.0"}')));

        $this->expectException(MalformedExitNodeListException::class);

        $downloader->downloadExitNodes();
    }

    private function httpClientReturning(Response $response): GuzzleHttpClient
    {
        return new GuzzleHttpClient($this->guzzleClient(new MockHandler([$response])));
    }

    private function guzzleClient(MockHandler $mockHandler): Client
    {
        $container = $this->transactions;

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($container));

        return new Client(['handler' => $handlerStack]);
    }

    private function requestedUri(): UriInterface
    {
        $transactions = $this->transactions->getArrayCopy();

        self::assertArrayHasKey(0, $transactions);

        $request = $transactions[0]['request'] ?? null;

        self::assertInstanceOf(RequestInterface::class, $request);

        return $request->getUri();
    }
}
