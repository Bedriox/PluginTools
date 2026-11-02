<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Packaging;

use Bedriox\PluginTools\Core\PluginPlatform;

final readonly class PluginManifest
{
    /**
     * @param list<string> $authors
     * @param list<string> $dependencies
     * @param list<string> $softDependencies
     */
    public function __construct(
        public int $schema,
        public PluginPlatform $platform,
        public string $name,
        public string $version,
        public string $api,
        public string $main,
        public string $namespace,
        public array $authors,
        public array $dependencies,
        public array $softDependencies,
        public string $load,
    ) {}

    public function normalizedJson(): string
    {
        return json_encode([
            'schema' => $this->schema,
            'platform' => $this->platform->value,
            'name' => $this->name,
            'version' => $this->version,
            'api' => $this->api,
            'main' => $this->main,
            'namespace' => $this->namespace,
            'authors' => $this->authors,
            'dependencies' => $this->dependencies,
            'softDependencies' => $this->softDependencies,
            'load' => $this->load,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }
}
