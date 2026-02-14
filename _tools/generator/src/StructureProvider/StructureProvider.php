<?php

declare(strict_types=1);

namespace Docs\Generator\StructureProvider;

interface StructureProvider
{
    /**
     * @return array<string, list<string>>
     * [pluginSlug => versionStrings]
     */
    public function getStructure(): array;
}