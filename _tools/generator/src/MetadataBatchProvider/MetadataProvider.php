<?php
declare(strict_types=1);

namespace Docs\Generator\MetadataBatchProvider;

use DateTimeImmutable;

interface MetadataProvider
{
    /**
     * @return array<string, array{
     *     name: string,
     *     updatedAt: ?DateTimeImmutable
     * }>
     */
    public function getLatestMetadata(): array;
}
