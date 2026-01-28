<?php
declare(strict_types=1);

namespace Docs\Generator;

final class PluginScanner
{
    private string $root;

    public function __construct(string $root)
    {
        if (!is_dir($root)) {
            throw new \RuntimeException("Invalid root directory: {$root}");
        }
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<string, list<SemVersion>> [pluginSlug => versions(sorted desc)]
     */
    public function scan(): array
    {
        $plugins = [];

        foreach (scandir($this->root) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (in_array($entry[0], ['.git'], true) || $entry[0] === '_') {
                continue;
            }

            $pluginPath = $this->root . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($pluginPath)) {
                continue;
            }

            $versions = $this->scanVersions($pluginPath);
            if ($versions === []) {
                continue;
            }

            usort(
                $versions,
                fn (SemVersion $a, SemVersion $b) => $b->compare($a)
            );

            $plugins[$entry] = $versions;
        }

        return $plugins;
    }

    /**
     * @return list<SemVersion>
     */
    private function scanVersions(string $pluginPath): array
    {
        $versions = [];

        foreach (scandir($pluginPath) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $path = $pluginPath . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                continue;
            }

            try {
                $versions[] = SemVersion::fromString($dir);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        return $versions;
    }
}
