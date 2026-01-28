<?php
declare(strict_types=1);

namespace Docs\Generator;

final class PluginRepository
{
    private string $root;
    private PluginScanner $scanner;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        $this->scanner = new PluginScanner($this->root);
    }

    /**
     * @return list<Plugin>
     */
    public function getAll(): array
    {
        $result = [];

        $scanned = $this->scanner->scan();

        foreach ($scanned as $slug => $versions) {
            $pluginPath = $this->root . DIRECTORY_SEPARATOR . $slug;
            $latest = $versions[0];

            $name = PluginMetadata::readSiteName($pluginPath, $latest);

            $updatedAt = $this->resolveUpdatedAt($pluginPath, $latest);

            $result[] = new Plugin(
                slug: $slug,
                name: $name,
                versions: $versions,
                updatedAt: $updatedAt
            );
        }

        usort(
            $result,
            fn (Plugin $a, Plugin $b) => strcasecmp($a->name, $b->name)
        );

        return $result;
    }

    private function resolveUpdatedAt(string $pluginPath, SemVersion $latest): \DateTimeImmutable
    {
        $mkdocs = $pluginPath
                  . DIRECTORY_SEPARATOR
                  . (string) $latest
                  . DIRECTORY_SEPARATOR
                  . 'mkdocs.yml';

        if (is_file($mkdocs)) {
            return new \DateTimeImmutable('@' . filemtime($mkdocs));
        }

        return new \DateTimeImmutable('@' . filemtime($pluginPath));
    }
}
