<?php
declare(strict_types=1);

namespace Docs\Generator;

use DateTimeImmutable;
use DateTimeZone;
use Docs\Generator\Infrastructure\SshConfiguration;
use Docs\Generator\MetadataBatchProvider\RemoteMetadataProvider;
use Docs\Generator\Rendering\DocumentationRenderer;
use Docs\Generator\StructureProvider\RemoteSshProvider;
use RuntimeException;
use Throwable;

final class DocumentationGenerator
{
    public function __construct(
        private SshConfiguration $ssh,
        private string $outputRoot
    ) {}

    public function run(): void
    {
        $structureProvider = new RemoteSshProvider(
            $this->ssh->sshBaseCommand,
            $this->ssh->remoteRoot
        );

        $metadataProvider = new RemoteMetadataProvider(
            $this->ssh->sshBaseCommand,
            $this->ssh->remoteRoot
        );

        $scanner = new PluginScanner($structureProvider);
        $repo = new PluginRepository($scanner, $metadataProvider);

        $plugins = $repo->getAll();

        try {
            $timezone = new DateTimeZone('Europe/Berlin');
            $now = new DateTimeImmutable('now', $timezone);
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to initialize DateTime', 0, $e);
        }

        $renderer = new DocumentationRenderer(
            $this->outputRoot,
            $now
        );
        $renderer->renderAll($plugins);
    }
}
