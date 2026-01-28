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

        return trim($data['site_name']);
    }
}
