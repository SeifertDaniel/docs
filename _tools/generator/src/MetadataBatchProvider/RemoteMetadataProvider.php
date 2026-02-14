<?php

declare(strict_types=1);

namespace Docs\Generator\MetadataBatchProvider;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class RemoteMetadataProvider implements MetadataProvider
{
    public function __construct(
        private string $sshBaseCommand,
        private string $remoteRoot
    ) {}

    public function getLatestMetadata(): array
    {
        $command = $this->buildCommand();

        exec($this->sshBaseCommand . ' ' . escapeshellarg($command), $output, $exit);

        if ($exit !== 0) {
            throw new RuntimeException('Failed fetching remote metadata');
        }

        return $this->parseStream(implode("\n", $output));
    }

    private function buildCommand(): string
    {
        $root = escapeshellarg($this->remoteRoot);

        return <<<BASH
find $root -mindepth 2 -maxdepth 2 -type d -printf '%P\n' \
| awk -F/ '{print \$1 " " \$2}' \
| sort -k1,1 -k2,2V \
| awk '
{
    if (\$1 != current) {
        if (current != "") print current " " version;
        current=\$1;
    }
    version=\$2;
}
END {
    if (current != "") print current " " version;
}
' \
| while read plugin version; do
    file=$root/\$plugin/\$version/mkdocs.yml
    if [ -f "\$file" ]; then
        echo "###FILE###:\$plugin/\$version"
        cat "\$file"
        echo ""
    fi
done
BASH;
    }

    private function parseStream(string $stream): array
    {
        $result = [];

        $blocks = preg_split('/^###FILE###:/m', $stream);

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            [$header, $yaml] = explode("\n", $block, 2);

            [$slug] = explode('/', trim($header), 2);

            try {
                $data = Yaml::parse($yaml);
            } catch (Throwable) {
                continue;
            }

            $name = $this->extractName($data, $slug);
            $updatedAt = $this->extractUpdatedAt($data);

            $result[$slug] = [
                'name' => $name,
                'updatedAt' => $updatedAt,
            ];
        }

        return $result;
    }

    private function extractName(array $data, string $fallback): string
    {
        if (!isset($data['site_name']) || !is_string($data['site_name'])) {
            return $fallback;
        }

        $name = trim($data['site_name']);

        if (str_contains($name, '·')) {
            $name = trim(explode('·', $name, 2)[0]);
        }

        return $name;
    }

    private function extractUpdatedAt(array $data): ?DateTimeImmutable
    {
        if (
            !isset($data['extra']['last_updated']) ||
            !is_string($data['extra']['last_updated'])
        ) {
            return null;
        }

        $dateString = trim($data['extra']['last_updated']);

        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $dateString . ' 12:00',
            new DateTimeZone('Europe/Berlin')
        ) ?: null;
    }
}
