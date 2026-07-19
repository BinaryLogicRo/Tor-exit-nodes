<?php

declare(strict_types=1);

namespace Tests\Feature\Storage;

use Binarylogic\TorExitNodes\Exception\SaveFailedException;
use Binarylogic\TorExitNodes\ExitNodeList;
use Binarylogic\TorExitNodes\Storage\ExitNodeFileWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Filesystem\Filesystem;
use Tests\TestCase;

#[CoversClass(ExitNodeFileWriter::class)]
final class ExitNodeFileWriterTest extends TestCase
{
    private string $workingDirectory;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workingDirectory = sys_get_temp_dir().'/tor-exit-nodes-writer-'.bin2hex(random_bytes(6));
        $this->filesystem->mkdir($this->workingDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workingDirectory);
    }

    public function test_writes_the_exit_node_list_as_json(): void
    {
        $path = $this->workingDirectory.'/exit-nodes.json';

        (new ExitNodeFileWriter($path))->saveExitNodes(ExitNodeList::fromIpAddresses(['185.220.101.1', '2001:db8::1']));

        self::assertFileExists($path);
        self::assertSame('{"exit_nodes":["185.220.101.1","2001:db8::1"]}', file_get_contents($path));
    }

    public function test_writes_an_empty_list(): void
    {
        $path = $this->workingDirectory.'/exit-nodes.json';

        (new ExitNodeFileWriter($path))->saveExitNodes(new ExitNodeList());

        self::assertSame('{"exit_nodes":[]}', file_get_contents($path));
    }

    public function test_creates_missing_directories(): void
    {
        $path = $this->workingDirectory.'/nested/storage/exit-nodes.json';

        (new ExitNodeFileWriter($path))->saveExitNodes(ExitNodeList::fromIpAddresses(['185.220.101.1']));

        self::assertFileExists($path);
    }

    public function test_overwrites_an_existing_file(): void
    {
        $path = $this->workingDirectory.'/exit-nodes.json';
        $writer = new ExitNodeFileWriter($path);

        $writer->saveExitNodes(ExitNodeList::fromIpAddresses(['185.220.101.1']));
        $writer->saveExitNodes(ExitNodeList::fromIpAddresses(['203.0.113.7']));

        self::assertSame('{"exit_nodes":["203.0.113.7"]}', file_get_contents($path));
    }

    public function test_fails_when_the_path_cannot_be_written(): void
    {
        $blockingFile = $this->workingDirectory.'/blocked';
        $this->filesystem->dumpFile($blockingFile, 'not a directory');

        $writer = new ExitNodeFileWriter($blockingFile.'/exit-nodes.json');

        $this->expectException(SaveFailedException::class);
        $this->expectExceptionMessage('Failed to save the exit node list to');

        $writer->saveExitNodes(ExitNodeList::fromIpAddresses(['185.220.101.1']));
    }

    public function test_keeps_the_filesystem_failure_as_the_previous_exception(): void
    {
        $blockingFile = $this->workingDirectory.'/blocked';
        $this->filesystem->dumpFile($blockingFile, 'not a directory');

        $writer = new ExitNodeFileWriter($blockingFile.'/exit-nodes.json');

        try {
            $writer->saveExitNodes(ExitNodeList::fromIpAddresses(['185.220.101.1']));
            self::fail('Expected a SaveFailedException to be thrown.');
        } catch (SaveFailedException $exception) {
            self::assertNotNull($exception->getPrevious());
        }
    }
}
