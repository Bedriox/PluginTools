<?php

declare(strict_types=1);

namespace Bedriox\Gateway\Api\Plugin;

abstract class Plugin
{
    final public function __construct(private readonly PluginContext $context) {}
    final public function context(): PluginContext
    {
        return $this->context;
    }
    public function onLoad(): void {}
    public function onEnable(): void {}
    public function onDisable(): void {}
}
