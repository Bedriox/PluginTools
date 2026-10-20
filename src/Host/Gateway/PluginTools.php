<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Host\Gateway;

use Bedriox\Gateway\Api\Plugin\Plugin;
use Bedriox\Gateway\Api\Plugin\PluginContext;
use Bedriox\Gateway\Api\Plugin\SourcePluginDefinition;
use Bedriox\PluginTools\Core\PluginPlatform;
use Bedriox\PluginTools\Development\SourcePluginClassLoader;
use Bedriox\PluginTools\Development\SourcePluginDiscovery;
use Bedriox\PluginTools\Development\SourcePluginResources;

/** Thin Gateway host adapter around the host-independent PluginTools core. */
final class PluginTools extends Plugin
{
    public function onLoad(): void
    {
        $registrar = $this->context()->sourcePlugins();
        $catalog = new SourcePluginDiscovery(platform: PluginPlatform::GATEWAY)->discover($registrar->pluginsDirectory());
        $definitions = [];
        foreach ($catalog->projects as $project) {
            $manifest = $project->manifestData;
            $loader = new SourcePluginClassLoader($project);
            $definitions[] = new SourcePluginDefinition(
                $manifest->schema,
                $manifest->platform->value,
                $manifest->name,
                $manifest->version,
                $manifest->api,
                $manifest->main,
                $manifest->namespace,
                $manifest->authors,
                $manifest->dependencies,
                $manifest->softDependencies,
                $manifest->load,
                new SourcePluginResources()->read($project),
                static function (PluginContext $context) use ($loader): Plugin {
                    $plugin = $loader->instantiate($context, Plugin::class);
                    assert($plugin instanceof Plugin);

                    return $plugin;
                },
                $loader->unregister(...),
            );
        }
        $registrar->register($definitions);
        $this->context()->logger()->info(sprintf(
            'Development source loading is active; discovered %d Gateway source plugin%s.',
            count($definitions),
            count($definitions) === 1 ? '' : 's',
        ));
        foreach ($catalog->failures as $failure) {
            $this->context()->logger()->warning("Source plugin {$failure->directory} was rejected: {$failure->reason}");
        }
    }

    public function onEnable(): void
    {
        $this->context()->logger()->info('Use the PluginTools CLI to package Gateway source plugins.');
    }
}
