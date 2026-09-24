<?php

declare(strict_types=1);

namespace Bedriox\PluginTools;

use Bedriox\Api\Plugin\Plugin;
use Bedriox\Api\Plugin\SourcePluginDefinition;
use Bedriox\PluginTools\Command\MakePluginCommand;
use Bedriox\PluginTools\Development\SourcePluginAutoloader;
use Bedriox\PluginTools\Development\SourcePluginDiscovery;
use Bedriox\PluginTools\Development\SourcePluginDiscoveryResult;

final class PluginTools extends Plugin
{
    private SourcePluginDiscoveryResult $catalog;

    public function onLoad(): void
    {
        $registrar = $this->context()->sourcePlugins();
        $this->catalog = new SourcePluginDiscovery()->discover($registrar->pluginsDirectory());
        $definitions = [];
        foreach ($this->catalog->projects as $project) {
            $manifest = $project->manifestData;
            $loader = new SourcePluginAutoloader($project);
            $definitions[] = new SourcePluginDefinition(
                $manifest->schema,
                $manifest->name,
                $manifest->version,
                $manifest->api,
                $manifest->main,
                $manifest->namespace,
                $manifest->authors,
                $manifest->dependencies,
                $manifest->softDependencies,
                $manifest->load,
                $loader->instantiate(...),
                $loader->unregister(...),
            );
        }
        $registrar->register($definitions);
        $this->logger()->info(sprintf(
            'Development source loading is active; discovered %d source plugin%s.',
            count($definitions),
            count($definitions) === 1 ? '' : 's',
        ));
        foreach ($this->catalog->failures as $failure) {
            $this->logger()->warning("Source plugin {$failure->directory} was rejected: {$failure->reason}");
        }
    }

    public function onEnable(): void
    {
        $commands = $this->context()->commands();
        $projects = array_map(
            static fn($project): string => $project->name(),
            $this->catalog->projects,
        );
        natcasesort($projects);
        $projectNames = $commands->registerSoftEnum('source_plugins', array_values($projects));
        $commands->register(new MakePluginCommand(
            $this->catalog,
            $this->context()->dataFolder(),
            $projectNames,
            $commands,
        ));
        $this->logger()->info('Use makeplugin <PluginName> [--overwrite] to package a source plugin.');
    }
}
