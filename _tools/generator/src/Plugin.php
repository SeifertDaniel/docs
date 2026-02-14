<?php
declare(strict_types=1);

namespace Docs\Generator;

use DateTimeImmutable;

final class Plugin
{
    public string $slug;
    public string $name;

    /** @var list<SemVersion> */
    public array $versions;

    public SemVersion $latest;
    public ?DateTimeImmutable $updatedAt;

    /**
     * @param list<SemVersion> $versions
     */
    public function __construct(
        string $slug,
        string $name,
        array $versions,
        ?DateTimeImmutable $updatedAt
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->versions = $versions;
        $this->latest = $versions[0];
        $this->updatedAt = $updatedAt;
    }
}
