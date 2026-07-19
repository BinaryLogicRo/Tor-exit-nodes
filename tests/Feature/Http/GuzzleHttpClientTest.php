<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use ArrayObject;
use Binarylogic\TorExitNodes\Exception\DownloadFailedException;
use Binarylogic\TorExitNodes\Http\GuzzleHttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

#[CoversClass(GuzzleHttpClient::class)]
final class GuzzleHttpClientTest extends TestCase
{
    private const URL = 'https://onionoo.torproject.org/details';

    /**
     * @var ArrayObject<int, array<array-key, mixed>>
     */
    private ArrayObject $transactions;

    protected function setUp(): void
    {
        $this->transactions = new ArrayObject();
    }

    public function test_returns_the_response_body(): void
    {
        $httpClient = $this->httpClientReturning(new Response(200, [], '{"relays":[]}'));

        self::assertSame('{"relays":[]}', $httpClient->fetchBody(self::URL));
    }

    public function test_requests_the_given_url_with_the_get_method(): void
    {
        $this->httpClientReturning(new Response(200, [], 'body'))->fetchBody(self::URL);

        self::assertSame('GET', $this->recordedRequest()->getMethod());
        self::assertSame(self::URL, (string) $this->recordedRequest()->getUri());
    }

    public function test_sends_the_default_user_agent_and_accepts_json(): void
    {
        $this->httpClientReturning(new Response(200, [], 'body'))->fetchBody(self::URL);

        $request = $this->recordedRequest();

        self::assertSame(GuzzleHttpClient::DEFAULT_USER_AGENT, $request->getHeaderLine('User-Agent'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function test_sends_a_configured_user_agent(): void
    {
        $httpClient = new GuzzleHttpClient(
            $this->guzzleClient(new MockHandler([new Response(200, [], 'body')])),
            userAgent: 'my-app/1.0',
        );

        $httpClient->fetchBody(self::URL);

        self::assertSame('my-app/1.0', $this->recordedRequest()->getHeaderLine('User-Agent'));
    }

    public function test_verifies_tls_certificates_and_applies_the_default_timeout(): void
    {
        $this->httpClientReturning(new Response(200, [], 'body'))->fetchBody(self::URL);

        $options = $this->recordedOptions();

        self::assertTrue($options[RequestOptions::VERIFY]);
        self::assertSame(GuzzleHttpClient::DEFAULT_TIMEOUT_SECONDS, $options[RequestOptions::TIMEOUT]);
        self::assertSame(GuzzleHttpClient::DEFAULT_TIMEOUT_SECONDS, $options[RequestOptions::CONNECT_TIMEOUT]);
    }

    public function test_applies_a_configured_timeout(): void
    {
        $httpClient = new GuzzleHttpClient(
            $this->guzzleClient(new MockHandler([new Response(200, [], 'body')])),
            timeoutSeconds: 2.5,
        );

        $httpClient->fetchBody(self::URL);

        $options = $this->recordedOptions();

        self::assertSame(2.5, $options[RequestOptions::TIMEOUT]);
        self::assertSame(2.5, $options[RequestOptions::CONNECT_TIMEOUT]);
    }

    public function test_fails_when_the_response_status_is_not_successful(): void
    {
        $httpClient = $this->httpClientReturning(new Response(503, [], 'Service Unavailable'));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionMessage('unexpected HTTP status code 503');

        $httpClient->fetchBody(self::URL);
    }

    public function test_fails_when_the_response_is_a_redirect_that_was_not_followed(): void
    {
        $httpClient = new GuzzleHttpClient(new Client([
            'handler' => $this->handlerStack(new MockHandler([new Response(302, ['Location' => 'https://example.com'], '')])),
            RequestOptions::ALLOW_REDIRECTS => false,
        ]));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionMessage('unexpected HTTP status code 302');

        $httpClient->fetchBody(self::URL);
    }

    public function test_fails_when_the_response_body_is_empty(): void
    {
        $httpClient = $this->httpClientReturning(new Response(200, [], ''));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionMessage('the response body was empty');

        $httpClient->fetchBody(self::URL);
    }

    public function test_fails_when_the_response_body_only_contains_whitespace(): void
    {
        $httpClient = $this->httpClientReturning(new Response(200, [], "  \n "));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionMessage('the response body was empty');

        $httpClient->fetchBody(self::URL);
    }

    public function test_fails_when_the_request_cannot_be_completed(): void
    {
        $mockHandler = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', self::URL)),
        ]);
        $httpClient = new GuzzleHttpClient($this->guzzleClient($mockHandler));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionMessage('Connection refused');

        $httpClient->fetchBody(self::URL);
    }

    public function test_keeps_the_transfer_failure_as_the_previous_exception(): void
    {
        $connectException = new ConnectException('Connection refused', new Request('GET', self::URL));
        $httpClient = new GuzzleHttpClient($this->guzzleClient(new MockHandler([$connectException])));

        try {
            $httpClient->fetchBody(self::URL);
            self::fail('Expected a DownloadFailedException to be thrown.');
        } catch (DownloadFailedException $exception) {
            self::assertSame($connectException, $exception->getPrevious());
        }
    }

    private function httpClientReturning(Response $response): GuzzleHttpClient
    {
        return new GuzzleHttpClient($this->guzzleClient(new MockHandler([$response])));
    }

    private function guzzleClient(MockHandler $mockHandler): Client
    {
        return new Client(['handler' => $this->handlerStack($mockHandler)]);
    }

    private function handlerStack(MockHandler $mockHandler): HandlerStack
    {
        $container = $this->transactions;

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($container));

        return $handlerStack;
    }

    private function recordedRequest(int $index = 0): RequestInterface
    {
        $request = $this->recordedTransaction($index)['request'] ?? null;

        self::assertInstanceOf(RequestInterface::class, $request);

        return $request;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function recordedOptions(int $index = 0): array
    {
        $options = $this->recordedTransaction($index)['options'] ?? null;

        self::assertIsArray($options);

        return $options;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function recordedTransaction(int $index): array
    {
        $transactions = $this->transactions->getArrayCopy();

        self::assertArrayHasKey($index, $transactions);

        return $transactions[$index];
    }
}
