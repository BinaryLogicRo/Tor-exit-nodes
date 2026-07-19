# Tor Exit Nodes

Download the Tor exit node list from the [Onionoo API](https://onionoo.torproject.org) and check whether an IP address belongs to a Tor exit node.

Every part of the package works on its own: download without saving, save without downloading, load without checking, check without any network or filesystem access.

## Requirements

- PHP 8.2 or newer

## Installation

```bash
composer require binarylogic/tor-exit-nodes
```

## Public API

| Class | Responsibility |
| --- | --- |
| `Binarylogic\TorExitNodes\Downloader\ExitNodeDownloader` | Retrieves the exit node list from Onionoo |
| `Binarylogic\TorExitNodes\Http\HttpClient` | HTTP abstraction the downloader depends on |
| `Binarylogic\TorExitNodes\Http\GuzzleHttpClient` | Guzzle implementation of `HttpClient` |
| `Binarylogic\TorExitNodes\Parser\OnionooExitNodeParser` | Turns an Onionoo JSON response into an `ExitNodeList` |
| `Binarylogic\TorExitNodes\Storage\ExitNodeFileWriter` | Saves an `ExitNodeList` to a JSON file |
| `Binarylogic\TorExitNodes\Storage\ExitNodeFileReader` | Loads an `ExitNodeList` from a JSON file |
| `Binarylogic\TorExitNodes\Storage\ExitNodeListJsonSerializer` | Encodes and decodes the stored JSON format |
| `Binarylogic\TorExitNodes\Checker\ExitNodeChecker` | Answers whether an IP address is an exit node |
| `Binarylogic\TorExitNodes\ExitNode` | Immutable single exit node address |
| `Binarylogic\TorExitNodes\ExitNodeList` | Immutable collection of `ExitNode` values |

### Download

```php
use Binarylogic\TorExitNodes\Downloader\ExitNodeDownloader;
use Binarylogic\TorExitNodes\Http\GuzzleHttpClient;

$downloader = new ExitNodeDownloader(new GuzzleHttpClient());

$exitNodes = $downloader->downloadExitNodes();

echo count($exitNodes);
echo implode(PHP_EOL, $exitNodes->allIpAddresses());
```

Nothing is written to disk. The result is an in-memory `ExitNodeList`.

To keep the raw Onionoo response instead of a parsed list:

```php
$json = $downloader->fetchRawList();
```

The endpoint, HTTP client and parser are all injectable:

```php
$downloader = new ExitNodeDownloader(
    httpClient: new GuzzleHttpClient(timeoutSeconds: 10.0, userAgent: 'my-app/1.0'),
    endpoint: 'https://onionoo.torproject.org/details?type=relay&running=true&flag=Exit&fields=or_addresses,exit_addresses',
);
```

To use an HTTP client other than Guzzle, implement `HttpClient`:

```php
use Binarylogic\TorExitNodes\Http\HttpClient;

final class MyHttpClient implements HttpClient
{
    public function fetchBody(string $url): string
    {
        // Return the response body, or throw a DownloadFailedException.
    }
}

$downloader = new ExitNodeDownloader(new MyHttpClient());
```

### Save

```php
use Binarylogic\TorExitNodes\Storage\ExitNodeFileWriter;

$writer = new ExitNodeFileWriter(__DIR__.'/storage/tor-exit-nodes.json');

$writer->saveExitNodes($exitNodes);
```

The writer accepts any `ExitNodeList`, whether it came from a download, a file, or was built by hand.

### Load

```php
use Binarylogic\TorExitNodes\Storage\ExitNodeFileReader;

$reader = new ExitNodeFileReader(__DIR__.'/storage/tor-exit-nodes.json');

$exitNodes = $reader->loadExitNodes();
```

### Check

```php
use Binarylogic\TorExitNodes\Checker\ExitNodeChecker;

$checker = new ExitNodeChecker($exitNodes);

if ($checker->isExitNode('185.220.101.1')) {
    // The request came through the Tor network.
}
```

`ExitNodeChecker` never touches the network or the filesystem. It works with a list from any origin:

```php
use Binarylogic\TorExitNodes\ExitNodeList;

$checker = new ExitNodeChecker(ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1']));
```

IPv4 and IPv6 addresses are both supported. Addresses are compared in canonical form, so `2001:DB8::1` and `2001:db8:0:0:0:0:0:1` are treated as the same node.

### Parse an already retrieved response

```php
use Binarylogic\TorExitNodes\Parser\OnionooExitNodeParser;

$exitNodes = (new OnionooExitNodeParser())->parseExitNodeList($json);
```

## Stored file format

```json
{"exit_nodes":["185.220.101.1","2001:db8::1"]}
```

## Exceptions

Every exception thrown by the package implements `Binarylogic\TorExitNodes\Exception\TorExitNodeException`, so all of them can be caught at once.

| Exception | Thrown when |
| --- | --- |
| `DownloadFailedException` | The list could not be retrieved over HTTP |
| `MalformedExitNodeListException` | A JSON payload is not a valid exit node list |
| `SaveFailedException` | The list could not be written to disk |
| `LoadFailedException` | The list could not be read from disk |
| `InvalidIpAddressException` | An IP address given to the package is not valid |

```php
use Binarylogic\TorExitNodes\Exception\TorExitNodeException;

try {
    $exitNodes = $downloader->downloadExitNodes();
} catch (TorExitNodeException $exception) {
    // Every failure mode of the package lands here.
}
```

## License

MIT
