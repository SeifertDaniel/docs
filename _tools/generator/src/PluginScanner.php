<?php
declare(strict_types=1);

namespace Docs\Generator;

use Docs\Generator\StructureProvider\StructureProvider;
use InvalidArgumentException;

final class PluginScanner
{
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
}
