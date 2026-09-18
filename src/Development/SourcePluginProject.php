<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Development;

use Bedriox\PluginTools\Packaging\PluginManifest;

final readonly class SourcePluginProject
{
    public function __construct(
        public PluginManifest $manifestData,
        public string $root,
        public string $manifest,
    ) {}

    public function name(): string
    {
        return $this->manifestData->name;
    }
}
