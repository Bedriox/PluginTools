<?php

declare(strict_types=1);

namespace Bedriox\Api\Plugin;

abstract class Plugin
{
    final public function __construct(private readonly PluginContext $context) {}

    final public function context(): PluginContext
    {
        return $this->context;
    }

    final public function logger(): PluginLogger
    {
        return new class implements PluginLogger {
            public function debug(string $message): void {}
            public function info(string $message): void {}
            public function warning(string $message): void {}
            public function error(string $message): void {}
        };
    }
    public function onLoad(): void {}
    public function onEnable(): void {}
}
