<?php

declare(strict_types=1);

namespace Bedriox\PluginTools;

use Bedriox\Api\Plugin\Plugin;

final class PluginTools extends Plugin
{
    public function onEnable(): void
    {
        $this->logger()->info('PluginTools is available through its standalone build command.');
    }
}
