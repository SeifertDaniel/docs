<?php
declare(strict_types=1);

namespace Docs\Generator\Infrastructure;

use RuntimeException;

final readonly class SshConfiguration
{
    public function __construct(
        public string $sshBaseCommand,
        public string $remoteRoot
    ) {}

    public static function fromEnvironment(): self
    {
        $remoteRoot = getenv('DEPLOY_TARGET_PATH');
        $port = (int)(getenv('DEPLOY_SSH_PORT') ?: 22);
        $user = getenv('DEPLOY_SSH_USER');
        $host = getenv('DEPLOY_SSH_HOST');

        if (!$remoteRoot || !$user || !$host) {
            throw new RuntimeException('Missing SSH configuration');
        }

        $sshBaseCommand = sprintf(
            'ssh -p %d -o StrictHostKeyChecking=no %s@%s',
            $port,
            escapeshellarg($user),
            escapeshellarg($host)
        );

        exec($sshBaseCommand . ' ' . escapeshellarg('echo ok'), $out, $exit);
        if ($exit !== 0) {
            throw new RuntimeException('SSH connection failed');
        }

        return new self($sshBaseCommand, rtrim($remoteRoot, '/'));
    }
}
