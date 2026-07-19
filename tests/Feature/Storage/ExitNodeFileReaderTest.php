<?php

declare(strict_types=1);

namespace Tests\Feature\Storage;

use Binarylogic\TorExitNodes\Exception\LoadFailedException;
use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\ExitNodeList;
use Binarylogic\TorExitNodes\Storage\ExitNodeFileReader;
use Binarylogic\TorExitNodes\Storage\ExitNodeFileWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Filesystem\Filesystem;
use Tests\TestCase;

#[CoversClass(ExitNodeFileReader::class)]
final class ExitNodeFileReaderTest extends TestCase
{
    private string $workingDirectory;

    private string $path;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workingDirectory = sys_get_temp_dir().'/tor-exit-nodes-reader-'.bin2hex(random_bytes(6));
        $this->path = $this->workingDirectory.'/exit-nodes.json';
        $this->filesystem->mkdir($this->workingDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workingDirectory);
    }

    public function test_loads_the_exit_node_list_from_a_file(): void
    {
        $this->filesystem->dumpFile($this->path, '{"exit_nodes":["185.220.101.1","2001:DB8::1"]}');

        $exitNodes = (new ExitNodeFileReader($this->path))->loadExitNodes();

        self::assertSame(['185.220.101.1', '2001:db8::1'], $exitNodes->allIpAddresses());
    }

    public function test_loads_an_empty_list(): void
    {
        $this->filesystem->dumpFile($this->path, '{"exit_nodes":[]}');

        self::assertTrue((new ExitNodeFileReader($this->path))->loadExitNodes()->isEmpty());
    }

    public function test_loads_a_list_written_by_the_writer(): void
    {
        $exitNodes = ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1', '203.0.113.7']);

        (new ExitNodeFileWriter($this->path))->saveExitNodes($exitNodes);

        self::assertSame($exitNodes->allIpAddresses(), (new ExitNodeFileReader($this->path))->loadExitNodes()->allIpAddresses());
    }

    public function test_fails_when_the_file_does_not_exist(): void
    {
        $reader = new ExitNodeFileReader($this->workingDirectory.'/missing.json');

        $this->expectException(LoadFailedException::class);
        $this->expectExceptionMessage('the file does not exist');

        $reader->loadExitNodes();
    }

    public function test_fails_when_the_file_is_not_valid_json(): void
    {
        $this->filesystem->dumpFile($this->path, 'not json at all');

        $reader = new ExitNodeFileReader($this->path);

        $this->expectException(LoadFailedException::class);
        $this->expectExceptionMessage('is not valid JSON');

        $reader->loadExitNodes();
    }

    public function test_fails_when_the_file_is_empty(): void
    {
        $this->filesystem->dumpFile($this->path, '');

        $reader = new ExitNodeFileReader($this->path);

        $this->expectException(LoadFailedException::class);

        $reader->loadExitNodes();
    }

    public function test_fails_when_the_file_has_no_exit_nodes_property(): void
    {
        $this->filesystem->dumpFile($this->path, '{"nodes":["185.220.101.1"]}');

        $reader = new ExitNodeFileReader($this->path);

        $this->expectException(LoadFailedException::class);
        $this->expectExceptionMessage('does not contain the expected "exit_nodes" property');

        $reader->loadExitNodes();
    }

    public function test_fails_when_the_file_contains_an_invalid_ip_address(): void
    {
        $this->filesystem->dumpFile($this->path, '{"exit_nodes":["185.220.101.1","not-an-ip"]}');

        $reader = new ExitNodeFileReader($this->path);

        $this->expectException(LoadFailedException::class);
        $this->expectExceptionMessage('an array of IP address strings');

        $reader->loadExitNodes();
    }

    public function test_keeps_the_malformed_list_failure_as_the_previous_exception(): void
    {
        $this->filesystem->dumpFile($this->path, 'not json at all');

        try {
            (new ExitNodeFileReader($this->path))->loadExitNodes();
            self::fail('Expected a LoadFailedException to be thrown.');
        } catch (LoadFailedException $exception) {
            self::assertInstanceOf(MalformedExitNodeListException::class, $exception->getPrevious());
        }
    }

    public function test_fails_when_the_path_cannot_be_read(): void
    {
        $reader = new ExitNodeFileReader($this->workingDirectory);

        $this->expectException(LoadFailedException::class);
        $this->expectExceptionMessage('Failed to read file');

        $reader->loadExitNodes();
    }
}
