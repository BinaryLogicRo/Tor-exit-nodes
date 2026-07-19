<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Storage;

use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\Exception\SaveFailedException;
use Binarylogic\TorExitNodes\ExitNodeList;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final class ExitNodeFileWriter
{
    private readonly Filesystem $filesystem;

    private readonly ExitNodeListJsonSerializer $serializer;

    public function __construct(
        private readonly string $path,
        ?Filesystem $filesystem = null,
        ?ExitNodeListJsonSerializer $serializer = null,
    ) {
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->serializer = $serializer ?? new ExitNodeListJsonSerializer();
    }

    /**
     * @throws SaveFailedException
     */
    public function saveExitNodes(ExitNodeList $exitNodes): void
    {
        try {
            $json = $this->serializer->encodeExitNodeList($exitNodes);
        } catch (MalformedExitNodeListException $exception) {
            throw SaveFailedException::forPath($this->path, $exception->getMessage(), $exception);
        }

        try {
            $this->filesystem->dumpFile($this->path, $json);
        } catch (IOException $exception) {
            throw SaveFailedException::forPath($this->path, $exception->getMessage(), $exception);
        }
    }
}
