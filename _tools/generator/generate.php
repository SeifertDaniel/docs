<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Docs\Generator\PluginScanner;

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "Root directory not found\n");
    exit(1);
}

$scanner = new PluginScanner($root);
$plugins = $scanner->scan();

foreach ($plugins as $plugin => $versions) {
    echo "Plugin: {$plugin}\n";
    echo "  latest: {$versions[0]}\n\n";
}
