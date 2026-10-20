<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Packaging;

use Bedriox\PluginTools\Core\PluginPlatform;

/** Builds one host-specific PluginTools PHAR from the shared repository sources. */
final class PluginToolsArtifactBuilder
{
    public function __construct(private readonly PluginPackager $packager = new PluginPackager()) {}

    public function build(string $repository, PluginPlatform $platform, string $output, bool $overwrite = false): string
    {
        $manifest = match ($platform) {
            PluginPlatform::BEDRIOX => $repository . DIRECTORY_SEPARATOR . 'plugin.json',
            PluginPlatform::GATEWAY => $repository . DIRECTORY_SEPARATOR . 'platforms' . DIRECTORY_SEPARATOR . 'gateway' . DIRECTORY_SEPARATOR . 'plugin.json',
        };

        return $this->packager->build($repository, $output, $overwrite, $manifest);
    }
}
