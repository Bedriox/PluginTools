<?php

declare(strict_types=1);

namespace Bedriox\Api\Plugin;

interface SourcePluginRegistrar
{
    public function pluginsDirectory(): string;
    /** @param list<SourcePluginDefinition> $definitions */
    public function register(array $definitions): void;
}
