<?php

declare(strict_types=1);

namespace Bedriox\Api\Plugin;

use Bedriox\Api\Command\CommandRegistrar;
use Bedriox\Api\Plugin\Data\PluginData;

final class PluginContext
{
    public function commands(): CommandRegistrar
    {
        throw new \LogicException('Test stub.');
    }
    public function sourcePlugins(): SourcePluginRegistrar
    {
        throw new \LogicException('Test stub.');
    }
    public function data(): PluginData
    {
        throw new \LogicException('Test stub.');
    }
}
