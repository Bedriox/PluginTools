<?php

declare(strict_types=1);

use Bedriox\PluginTools\Packaging\PluginToolsArtifactBuilder;

require dirname(__DIR__) . '/vendor/autoload.php';

$arguments = array_slice($argv, 1);
$overwrite = false;
if (($key = array_search('--overwrite', $arguments, true)) !== false) {
    $overwrite = true;
    array_splice($arguments, (int) $key, 1);
}
if (count($arguments) !== 1) {
    fwrite(STDERR, "Usage: php -d phar.readonly=0 bin/build-artifact.php <PluginTools.phar> [--overwrite]\n");
    exit(2);
}

try {
    $name = new PluginToolsArtifactBuilder()->build(dirname(__DIR__), $arguments[0], $overwrite);
    fwrite(STDOUT, "Built {$name}: {$arguments[0]}\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Build failed: ' . $error->getMessage() . "\n");
    exit(1);
}
