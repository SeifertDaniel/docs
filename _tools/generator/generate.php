<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Docs\Generator\MetadataBatchProvider\RemoteMetadataProvider;
use Docs\Generator\PluginRepository;
use Docs\Generator\PluginScanner;
use Docs\Generator\StructureProvider\RemoteSshProvider;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * locale output root
 */

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "Root directory not found\n");
    exit(1);
}

/**
 * remote configuration
 */

$remotePath = getenv('DEPLOY_TARGET_PATH');
if (!$remotePath) {
    throw new RuntimeException('Missing DEPLOY_TARGET_PATH');
}

if (!str_starts_with($remotePath, '/')) {
    throw new RuntimeException('Remote path must be absolute');
}

$remotePath = rtrim($remotePath, '/');
fwrite(STDOUT, sprintf("Remote path: %s\n", $remotePath));

$port = (int)(getenv('DEPLOY_SSH_PORT') ?: 22);
$user = getenv('DEPLOY_SSH_USER');
$host = getenv('DEPLOY_SSH_HOST');

if (!$user || !$host) {
    throw new RuntimeException('Missing SSH credentials');
}

$sshBaseCommand = sprintf(
    'ssh -p %d -o StrictHostKeyChecking=no %s@%s',
    $port,
    escapeshellarg($user),
    escapeshellarg($host)
);

/**
 * SSH connection test
 */

exec($sshBaseCommand . ' echo ok', $out, $exit);
if ($exit !== 0) {
    throw new RuntimeException('SSH connection failed');
}

/**
 * create provider + repository
 */

$structureProvider = new RemoteSshProvider(
    $sshBaseCommand,
    $remotePath
);
$scanner = new PluginScanner($structureProvider);
$metadataProvider = new RemoteMetadataProvider(
    $sshBaseCommand,
    $remotePath
);

$repo = new PluginRepository($scanner, $metadataProvider);
$plugins = $repo->getAll();

/**
 * initialize twig
 */

$loader = new FilesystemLoader(__DIR__ . '/templates/twig');

$twig = new Environment($loader, [
    'cache' => false,
    'timezone' => 'Europe/Berlin',
]);

try {
    $timezone = new DateTimeZone('Europe/Berlin');
    $now = new DateTimeImmutable('now', $timezone);
} catch (Throwable $e) {
    throw new RuntimeException(
        'Failed to initialize DateTime',
        previous: $e
    );
}

/**
 * generate root index
 */

$html = $twig->render('root.html.twig', [
    'plugins'     => $plugins,
    'generatedAt' => $now,
]);

if (file_put_contents($root . '/index.html', $html) === false) {
    throw new RuntimeException('Failed writing root index.html');
}

/**
 * generate plugin pages
 */

foreach ($plugins as $plugin) {

    $dir = $root . '/' . $plugin->slug;

    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf("Failed to create directory: %s", $dir));
        }
    }

    // plugin index
    $html = $twig->render('plugin.html.twig', [
        'plugin' => $plugin,
    ]);

    if (file_put_contents($dir . '/index.html', $html) === false) {
        throw new RuntimeException(sprintf("Failed writing plugin index for %s", $plugin->slug));
    }

    // latest redirect
    $latestDir = $dir . '/latest';

    if (!is_dir($latestDir)) {
        mkdir($latestDir, 0755, true);
    }

    $redirectHtml = $twig->render('redirect.html.twig', [
        'target' => '/' . $plugin->slug . '/' . $plugin->latest . '/',
    ]);

    if (file_put_contents($latestDir . '/index.html', $redirectHtml) === false) {
        throw new RuntimeException(sprintf("Failed writing latest redirect for %s", $plugin->slug));
    }
}

/**
 * generate sitemap
 */

$sitemap = $twig->render('sitemap.xml.twig', [
    'plugins'     => $plugins,
    'generatedAt' => $now,
    'baseUrl'     => 'https://docs.oxidmodule.com',
]);

if (file_put_contents($root . '/sitemap.xml', $sitemap) === false) {
    throw new RuntimeException('Failed writing sitemap.xml');
}

fwrite(STDOUT, "Documentation generated successfully\n");
