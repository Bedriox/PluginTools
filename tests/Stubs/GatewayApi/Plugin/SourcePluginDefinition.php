<?php

declare(strict_types=1);

namespace Bedriox\Gateway\Api\Plugin;

use Closure;

final readonly class SourcePluginDefinition
{
    /**
     * @param list<string> $authors
     * @param list<string> $dependencies
     * @param list<string> $softDependencies
     * @param array<string, string> $resources
     */
    public function __construct(
        public int $schema,
        public string $platform,
        public string $name,
        public string $version,
        public string $api,
        public string $main,
        public string $namespace,
        public array $authors,
        public array $dependencies,
        public array $softDependencies,
        public string $load,
        public array $resources,
        public Closure $instantiate,
        public ?Closure $cleanup = null,
    ) {}
}
