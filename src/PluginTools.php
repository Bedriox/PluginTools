<?php

declare(strict_types=1);

namespace Bedriox\PluginTools;

use Bedriox\Api\Command\AllowedCommandSenders;
use Bedriox\Api\Command\CommandContext;
use Bedriox\Api\Command\CommandDefinition;
use Bedriox\Api\Command\CommandResult;
use Bedriox\Api\Plugin\Plugin;
use Bedriox\Api\Plugin\SourcePluginDefinition;
use Bedriox\PluginTools\Command\MakePluginPlanner;
use Bedriox\PluginTools\Command\PackageBuildJob;
use Bedriox\PluginTools\Development\SourcePluginAutoloader;
use Bedriox\PluginTools\Development\SourcePluginDiscovery;
use Bedriox\PluginTools\Development\SourcePluginDiscoveryResult;
use InvalidArgumentException;
use Phar;
use Throwable;

final class PluginTools extends Plugin
{
    private SourcePluginDiscoveryResult $catalog;
    /** @var array<string, true> */
    private array $activeBuilds = [];

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
        $this->context()->commands()->register(
            new CommandDefinition(
                'makeplugin',
                'Builds a discovered source plugin into a signed PHAR.',
                'makeplugin <PluginName> [--overwrite]',
                permission: 'plugintools.command.makeplugin',
                allowedSenders: AllowedCommandSenders::CONSOLE_ONLY,
            ),
            $this->makePlugin(...),
        );
        $this->logger()->info('Use makeplugin <PluginName> [--overwrite] to package a source plugin.');
    }

    private function makePlugin(CommandContext $context): CommandResult
    {
        try {
            $plan = new MakePluginPlanner()->plan($context->arguments(), $this->catalog, $this->context()->dataFolder());
        } catch (InvalidArgumentException $failure) {
            if (str_starts_with($failure->getMessage(), 'Usage:')) {
                return CommandResult::USAGE;
            }
            $context->sender()->sendMessage($failure->getMessage());
            return CommandResult::FAILURE;
        } catch (Throwable $failure) {
            $context->sender()->sendMessage('The plugin build could not be prepared: ' . $failure->getMessage());
            return CommandResult::FAILURE;
        }
        $key = strtolower($plan->project->name());
        if (isset($this->activeBuilds[$key])) {
            $context->sender()->sendMessage("A build for {$plan->project->name()} is already running.");
            return CommandResult::FAILURE;
        }
        $archive = Phar::running(false);
        if ($archive === '') {
            $context->sender()->sendMessage('PluginTools must be installed as a PHAR to use makeplugin.');
            return CommandResult::FAILURE;
        }
        $this->activeBuilds[$key] = true;
        try {
            $job = new PackageBuildJob(
                $plan,
                $archive,
                $context->sender(),
                function () use ($key): void {
                    unset($this->activeBuilds[$key]);
                },
            );
            $this->context()->commands()->submitJob($job);
        } catch (Throwable $failure) {
            unset($this->activeBuilds[$key]);
            $context->sender()->sendMessage('The packaging worker could not be started: ' . $failure->getMessage());
            return CommandResult::FAILURE;
        }
        $context->sender()->sendMessage("Started packaging {$plan->project->name()}.");
        return CommandResult::SUCCESS;
    }
}
