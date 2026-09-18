<?php

declare(strict_types=1);

namespace Bedriox\Api\Plugin;

abstract class Plugin
{
    final public function logger(): PluginLogger
    {
        return new class implements PluginLogger {
            public function info(string $message): void {}
        };
    }
    public function onEnable(): void {}
}
