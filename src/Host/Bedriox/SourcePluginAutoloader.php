<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Host\Bedriox;

use Bedriox\Api\Plugin\Plugin;
use Bedriox\Api\Plugin\PluginContext;
use Bedriox\PluginTools\Development\SourcePluginClassLoader;
use Bedriox\PluginTools\Development\SourcePluginProject;

/** Namespace-restricted Bedriox loader used only after the host admits a definition. */
final class SourcePluginAutoloader
{
    private SourcePluginClassLoader $loader;

    public function __construct(SourcePluginProject $project)
    {
        $this->loader = new SourcePluginClassLoader($project);
    }

    public function unregister(): void
    {
        $this->loader->unregister();
    }

    public function instantiate(PluginContext $context): Plugin
    {
        $plugin = $this->loader->instantiate($context, Plugin::class);
        assert($plugin instanceof Plugin);

        return $plugin;
    }
}
