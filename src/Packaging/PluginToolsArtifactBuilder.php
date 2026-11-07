<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Packaging;

/** Builds one PluginTools PHAR for Bedriox, Gateway, and standalone CLI use. */
final class PluginToolsArtifactBuilder
{
    public function __construct(private readonly PluginPackager $packager = new PluginPackager()) {}

    public function build(string $repository, string $output, bool $overwrite = false): string
    {
        $gatewayManifest = $repository . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, PluginPackager::GATEWAY_MANIFEST);
        if (!is_file($gatewayManifest) || is_link($gatewayManifest)) {
            throw new PackageException('PluginTools requires its Gateway platform manifest.');
        }

        return $this->packager->build($repository, $output, $overwrite);
    }
}
