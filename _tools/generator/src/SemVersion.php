<?php
declare(strict_types=1);

namespace Docs\Generator;

final class SemVersion
{
    private int $major;
    private int $minor;
    private int $patch;
    private int $build;

    private string $original;

    private function __construct(
        int $major,
        int $minor,
        int $patch,
        int $build,
        string $original
    ) {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->build = $build;
        $this->original = $original;
    }

    public static function fromString(string $version): self
    {
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $version, $m)) {
            throw new \InvalidArgumentException("Invalid version string: {$version}");
        }

        return new self(
            (int) $m[1],
            (int) $m[2],
            (int) $m[3],
            (int) $m[4],
            $version
        );
    }

    public function compare(self $other): int
    {
        foreach (['major', 'minor', 'patch', 'build'] as $field) {
            $diff = $this->$field <=> $other->$field;
            if ($diff !== 0) {
                return $diff;
            }
        }

        return 0;
    }

    public function __toString(): string
    {
        return $this->original;
    }
}
