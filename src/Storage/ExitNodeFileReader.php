<?php

declare(strict_types=1);

namespace Binarylogic\TorExitNodes\Storage;

use Binarylogic\TorExitNodes\Exception\LoadFailedException;
use Binarylogic\TorExitNodes\Exception\MalformedExitNodeListException;
use Binarylogic\TorExitNodes\ExitNodeList;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final class ExitNodeFileReader
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
     * @throws LoadFailedException
     */
    public function loadExitNodes(): ExitNodeList
    {
        if (! $this->filesystem->exists($this->path)) {
            throw LoadFailedException::forMissingFile($this->path);
        }

        try {
            $json = $this->filesystem->readFile($this->path);
        } catch (IOException $exception) {
            throw LoadFailedException::forUnreadableFile($this->path, $exception->getMessage(), $exception);
        }

        try {
            return $this->serializer->decodeExitNodeList($json);
        } catch (MalformedExitNodeListException $exception) {
            throw LoadFailedException::forMalformedFile($this->path, $exception);
        }
    }
}
