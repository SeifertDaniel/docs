<?php
declare(strict_types=1);

namespace Docs\Generator\StructureProvider;

use RuntimeException;

final class RemoteSshProvider implements StructureProvider
{
    public function __construct(
        private string $sshBaseCommand,
        private string $remoteRoot
    ) {}

    public function getStructure(): array
    {
        $command = sprintf(
            "find %s -mindepth 2 -maxdepth 2 -type d -printf '%%P\n'",
            escapeshellarg($this->remoteRoot)
        );

        exec(
            $this->sshBaseCommand . ' ' . escapeshellarg($command),
            $output,
            $exit
        );

        if ($exit !== 0) {
            throw new RuntimeException('Remote structure scan failed');
        }

        $result = [];

        foreach ($output as $line) {
            [$slug, $version] = explode('/', trim($line), 2);
            $result[$slug][] = $version;
        }

        return $result;
    }
}
