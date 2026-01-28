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

$loader = new FilesystemLoader(__DIR__ . '/templates/twig');
$twig = new Environment($loader, [
    'cache' => false,
]);

$html = $twig->render('root.html.twig', [
    'plugins' => $plugins,
    'generatedAt' => new DateTimeImmutable(),
]);

file_put_contents($root . '/index.html', $html);

// Plugin-Index-Seiten rendern
foreach ($plugins as $plugin) {
    $html = $twig->render('plugin.html.twig', [
        'plugin' => $plugin,
    ]);

    $target = $root . '/' . $plugin->slug . '/index.html';
    file_put_contents($target, $html);
}

echo "Root index.html generated\n";