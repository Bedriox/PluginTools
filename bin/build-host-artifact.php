<?php

declare(strict_types=1);

use Bedriox\PluginTools\Core\PluginPlatform;
use Bedriox\PluginTools\Packaging\PluginToolsArtifactBuilder;

require dirname(__DIR__) . '/vendor/autoload.php';

$arguments = array_slice($argv, 1);
$overwrite = false;
if (($key = array_search('--overwrite', $arguments, true)) !== false) {
    $overwrite = true;
    array_splice($arguments, (int) $key, 1);
}
if (count($arguments) !== 2 || ($platform = PluginPlatform::tryFrom($arguments[0])) === null) {
    fwrite(STDERR, "Usage: php -d phar.readonly=0 bin/build-host-artifact.php <bedriox|gateway> <output.phar> [--overwrite]\n");
    exit(2);
}

try {
    $name = new PluginToolsArtifactBuilder()->build(dirname(__DIR__), $platform, $arguments[1], $overwrite);
    fwrite(STDOUT, "Built {$name} for {$platform->value}: {$arguments[1]}\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Build failed: ' . $error->getMessage() . "\n");
    exit(1);
}
