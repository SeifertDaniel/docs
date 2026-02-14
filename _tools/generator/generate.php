<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Docs\Generator\PluginRepository;
use Docs\Generator\StructureProvider\RemoteSshProvider;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "Root directory not found\n");
    exit(1);
}

$remotePath = getenv('DEPLOY_TARGET_PATH');
if (!$remotePath) {
    throw new RuntimeException('Missing DEPLOY_TARGET_PATH');
}
if (!str_starts_with($remotePath, '/')) {
    throw new RuntimeException('Remote path must be absolute');
}
$remotePath = rtrim($remotePath, '/');
fwrite(STDOUT, "Remote path: $remotePath\n");

$port = getenv('DEPLOY_SSH_PORT') ?: 22;
$user = getenv('DEPLOY_SSH_USER');
$host = getenv('DEPLOY_SSH_HOST');

$sshBaseCommand = sprintf(
    'ssh -p %d -o StrictHostKeyChecking=no %s@%s',
    (int)$port,
    escapeshellarg($user),
    escapeshellarg($host)
);

exec($sshBaseCommand . ' echo ok', $out, $exit);
if ($exit !== 0) {
    throw new RuntimeException('SSH connection failed');
}

$findCommand = sprintf(
    "find %s -mindepth 2 -maxdepth 2 -type d -printf '%%P\n'",
    escapeshellarg($remotePath)
);

$cmd = $sshBaseCommand . ' ' . escapeshellarg($findCommand);
exec($cmd, $output, $exitCode);
if ($exitCode !== 0) {
    throw new RuntimeException('Remote scan failed');
}

$provider = new RemoteSshProvider($cmd);

$repo = new PluginRepository($root, $provider);
$plugins = $repo->getAll();

$loader = new FilesystemLoader(__DIR__ . '/templates/twig');
$twig = new Environment($loader, [
    'cache' => false,
    'timezone' => 'Europe/Berlin',
]);

$html = $twig->render('root.html.twig', [
    'plugins' => $plugins,
    'generatedAt' => new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')),
]);
file_put_contents($root . '/index.html', $html);

// Plugin-Index-Seiten rendern
foreach ($plugins as $plugin) {
    $html = $twig->render('plugin.html.twig', [
        'plugin' => $plugin,
    ]);

    $dir = $root . '/' . $plugin->slug;
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: $dir");
        }
    }

    $target = $dir . '/index.html';
    file_put_contents($target, $html);

    // latest-Redirect erzeugen
    $latestDir = $dir . '/latest';
    if (!is_dir($latestDir)) {
        mkdir($latestDir, 0755, true);
    }

    $redirectHtml = $twig->render('redirect.html.twig', [
        'target' => '/' . $plugin->slug . '/' . $plugin->latest . '/',
    ]);
    file_put_contents($latestDir . '/index.html', $redirectHtml);
}

$sitemap = $twig->render('sitemap.xml.twig', [
    'plugins' => $plugins,
    'generatedAt' => new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')),
    'baseUrl' => 'https://docs.oxidmodule.com',
]);
file_put_contents($root . '/sitemap.xml', $sitemap);

echo "Base page generated\n";