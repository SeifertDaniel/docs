<?php
declare(strict_types=1);

namespace Docs\Generator;

use Docs\Generator\MetadataBatchProvider\MetadataProvider;

final class PluginRepository
{
    private PluginScanner $scanner;
    private MetadataProvider $metadataProvider;

    public function __construct(
        PluginScanner $scanner,
        MetadataProvider $metadataProvider
    ) {
        $this->scanner = $scanner;
        $this->metadataProvider = $metadataProvider;
    }

    public function getAll(): array
    {
        $result = [];

        $scanned = $this->scanner->scan();

        if ($scanned === []) {
            return [];
        }

        $metadataMap = $this->metadataProvider->getLatestMetadata();

        foreach ($scanned as $slug => $versions) {
            $name = $metadataMap[$slug]['name'] ?? $slug;
            $updatedAt = $metadataMap[$slug]['updatedAt'] ?? null;

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
}
