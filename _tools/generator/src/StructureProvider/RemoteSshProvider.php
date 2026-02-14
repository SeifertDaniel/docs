<?php
declare(strict_types=1);

namespace Docs\Generator\StructureProvider;

use RuntimeException;

final class RemoteSshProvider implements StructureProvider
{
    public function __construct(private string $sshCommand) {}

    public function getStructure(): array
    {
        exec($this->sshCommand, $output, $exit);

        if ($exit !== 0) {
            throw new RuntimeException("SSH failed");
        }

        $result = [];

        foreach ($output as $line) {
            $parts = explode('/', trim($line));
            $slug = $parts[count($parts)-2];
            $version = $parts[count($parts)-1];

            $result[$slug][] = $version;
        }

        return $result;
    }
}
