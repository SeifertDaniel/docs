<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Docs\Generator\DocumentationGenerator;
use Docs\Generator\Infrastructure\SshConfiguration;

/**
 * locale output root
 */

$root = realpath(__DIR__ . '/../../');

$ssh = SshConfiguration::fromEnvironment();

$generator = new DocumentationGenerator(
    $ssh,
    $root
);

$generator->run();

fwrite(STDOUT, "Documentation generated successfully\n");
