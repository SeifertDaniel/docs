<?php
declare(strict_types=1);

namespace Docs\Generator;

use Symfony\Component\Yaml\Yaml;

final class PluginMetadata
{
    public static function readSiteName(string $pluginPath, SemVersion $latest): string
    {
        $mkdocsPath = $pluginPath
                      . DIRECTORY_SEPARATOR
                      . (string) $latest
                      . DIRECTORY_SEPARATOR
                      . 'mkdocs.yml';

        if (!is_file($mkdocsPath)) {
            return basename($pluginPath);
        }

        try {
            $data = Yaml::parseFile($mkdocsPath);
        } catch (\Throwable) {
            return basename($pluginPath);
        }

        if (!isset($data['site_name']) || !is_string($data['site_name'])) {
            return basename($pluginPath);
        }

        $name = trim($data['site_name']);

        if (str_contains($name, '·')) {
            $name = trim(explode('·', $name, 2)[0]);
        }

        return $name;
    }

    public static function readUpdateDate(string $pluginPath, SemVersion $latest): ?\DateTimeImmutable
    {
        $mkdocsPath = $pluginPath
                      . DIRECTORY_SEPARATOR
                      . (string) $latest
                      . DIRECTORY_SEPARATOR
                      . 'mkdocs.yml';

        if (!is_file($mkdocsPath)) {
            return null;
        }

        try {
            $data = Yaml::parseFile($mkdocsPath);
        } catch (\Throwable) {
            return null;
        }

        if (
            !isset($data['extra']['last_updated']) ||
            !is_string($data['extra']['last_updated'])
        ) {
            return null;
        }

        $dateString = trim($data['extra']['last_updated']);

        try {
            return new \DateTimeImmutable(
                $dateString,
                new \DateTimeZone('Europe/Berlin')
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
