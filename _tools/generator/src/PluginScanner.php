<?php
declare(strict_types=1);

namespace Docs\Generator;

use Docs\Generator\StructureProvider\StructureProvider;
use InvalidArgumentException;

final class PluginScanner
{
    private string $root;

    public function __construct(
        private StructureProvider $provider
    ) {}

    /**
     * @return array<string, list<SemVersion>> [pluginSlug => versions(sorted desc)]
     */
    public function scan(): array
    {
        $plugins = [];

        foreach ($this->provider->getStructure() as $slug => $versions) {

            $semVersions = [];

            foreach ($versions as $version) {
                try {
                    $semVersions[] = SemVersion::fromString($version);
                } catch (InvalidArgumentException) {
                    continue;
                }
            }

            usort(
                $semVersions,
                fn (SemVersion $a, SemVersion $b) => $b->compare($a)
            );

            if ($semVersions !== []) {
                $plugins[$slug] = $semVersions;
            }
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
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $versions;
    }
}
