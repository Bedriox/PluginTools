<?php

declare(strict_types=1);

namespace Bedriox\Gateway\Api\Plugin;

final readonly class PluginContext
{
    public function __construct(private PluginLogger $logger, private SourcePluginRegistrar $sourcePlugins) {}
    public function logger(): PluginLogger
    {
        return $this->logger;
    }
    public function sourcePlugins(): SourcePluginRegistrar
    {
        return $this->sourcePlugins;
    }
}
