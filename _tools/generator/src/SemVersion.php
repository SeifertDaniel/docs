<?php
declare(strict_types=1);

namespace Docs\Generator;

final class SemVersion
{
    private int $major;
    private int $minor;
    private int $subminor;
    private int $patch;

    private string $original;

    private function __construct(
        int $major,
        int $minor,
        int $subminor,
        int $patch,
        string $original
    ) {
        $this->major = $major;
        $this->minor = $minor;
        $this->subminor = $subminor;
        $this->patch = $patch;
        $this->original = $original;
    }

    public static function fromString(string $version): self
    {
        //  x.y.z        ? x.y.0.z
        //  x.y.z.p      ? x.y.z.p
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)(?:\.(\d+))?$/', $version, $m)) {
            throw new \InvalidArgumentException("Invalid version string: {$version}");
        }

        $major = (int) $m[1];
        $minor = (int) $m[2];

        if (isset($m[4])) {
            // 4-stellig
            $subminor = (int) $m[3];
            $patch    = (int) $m[4];
        } else {
            // 3-stellig ? Subminor einschieben
            $subminor = 0;
            $patch    = (int) $m[3];
        }

        return new self(
            $major,
            $minor,
            $subminor,
            $patch,
            $version
        );
    }

    public function compare(self $other): int
    {
        foreach (['major', 'minor', 'subminor', 'patch'] as $field) {
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
