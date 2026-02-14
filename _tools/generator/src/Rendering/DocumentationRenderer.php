<?php
declare(strict_types=1);

namespace Docs\Generator\Rendering;

use DateTimeImmutable;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class DocumentationRenderer
{
    private Environment $twig;

    public function __construct(
        private readonly string   $outputRoot,
        private readonly DateTimeImmutable $now
    ) {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates/twig');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'timezone' => 'Europe/Berlin',
        ]);
    }

    public function renderAll(array $plugins): void
    {
        $this->renderRoot($plugins);
        $this->renderPlugins($plugins);
        $this->renderSitemap($plugins);
    }

    private function renderRoot(array $plugins): void
    {
        $html = $this->twig->render('root.html.twig', [
            'plugins'     => $plugins,
            'generatedAt' => $this->now,
        ]);

        if (file_put_contents($this->outputRoot . '/index.html', $html) === false) {
            throw new RuntimeException('Failed writing root index.html');
        }
    }

    private function renderPlugins(array $plugins): void
    {
        foreach ($plugins as $plugin) {

            $dir = $this->outputRoot . '/' . $plugin->slug;

            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new RuntimeException(sprintf("Failed to create directory: %s", $dir));
                }
            }

            // plugin index
            $html = $this->twig->render('plugin.html.twig', [
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

            $redirectHtml = $this->twig->render('redirect.html.twig', [
                'target' => '/' . $plugin->slug . '/' . $plugin->latest . '/',
            ]);

            if (file_put_contents($latestDir . '/index.html', $redirectHtml) === false) {
                throw new RuntimeException(sprintf("Failed writing latest redirect for %s", $plugin->slug));
            }
        }
    }

    private function renderSitemap(array $plugins): void
    {
        $sitemap = $this->twig->render('sitemap.xml.twig', [
            'plugins'     => $plugins,
            'generatedAt' => $this->now,
            'baseUrl'     => 'https://docs.oxidmodule.com',
        ]);

        if (file_put_contents($this->outputRoot . '/sitemap.xml', $sitemap) === false) {
            throw new RuntimeException('Failed writing sitemap.xml');
        }
    }
}
