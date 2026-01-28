<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Docs\Generator\PluginRepository;

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "Root directory not found\n");
    exit(1);
}

$repo = new PluginRepository($root);
$plugins = $repo->getAll();

foreach ($plugins as $plugin) {
    echo "{$plugin->name} ({$plugin->slug})\n";
    echo "  latest: {$plugin->latest}\n";
    echo "  updated: {$plugin->updatedAt->format('Y-m-d H:i:s')}\n\n";
}
