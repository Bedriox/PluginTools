<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Command;

use Bedriox\Api\Command\AbstractCommand;
use Bedriox\Api\Command\AllowedCommandSenders;
use Bedriox\Api\Command\CommandArguments;
use Bedriox\Api\Command\CommandContext;
use Bedriox\Api\Command\CommandOverload;
use Bedriox\Api\Command\CommandParameter;
use Bedriox\Api\Command\CommandRegistrar;
use Bedriox\Api\Command\CommandResult;
use Bedriox\Api\Command\CommandSoftEnum;
use Bedriox\PluginTools\Development\SourcePluginDiscoveryResult;
use Phar;
use Throwable;

final class MakePluginCommand extends AbstractCommand
{
    /** @var array<string, true> */
    private array $activeBuilds = [];

    public function __construct(
        private readonly SourcePluginDiscoveryResult $catalog,
        private readonly string $dataFolder,
        private readonly CommandSoftEnum $projectNames,
        private readonly CommandRegistrar $commands,
    ) {
        parent::__construct('makeplugin', 'Builds a discovered source plugin into a signed PHAR.');
    }

    public function defineArguments(): CommandArguments
    {
        return CommandArguments::create()
            ->addOverload(CommandOverload::create()
                ->addArgument(CommandParameter::softEnum('project', $this->projectNames)))
            ->addOverload(CommandOverload::create()
                ->addArgument(CommandParameter::softEnum('project', $this->projectNames))
                ->addArgument(CommandParameter::literal('--overwrite')));
    }

    public function execute(CommandContext $context): CommandResult
    {
        $arguments = [$context->values()->string('project')];
        if ($context->values()->has('overwrite')) {
            $arguments[] = $context->values()->choice('overwrite');
        }
        try {
            $plan = new MakePluginPlanner()->plan($arguments, $this->catalog, $this->dataFolder);
        } catch (Throwable $failure) {
            return $this->failure('The plugin build could not be prepared: ' . $failure->getMessage());
        }
        $key = strtolower($plan->project->name());
        if (isset($this->activeBuilds[$key])) {
            return $this->failure("A build for {$plan->project->name()} is already running.");
        }
        $archive = Phar::running(false);
        if ($archive === '') {
            return $this->failure('PluginTools must be installed as a PHAR to use makeplugin.');
        }
        $this->activeBuilds[$key] = true;
        try {
            $this->commands->submitJob(new PackageBuildJob(
                $plan,
                $archive,
                $context->sender(),
                function () use ($key): void {
                    unset($this->activeBuilds[$key]);
                },
            ));
        } catch (Throwable $failure) {
            unset($this->activeBuilds[$key]);

            return $this->failure('The packaging worker could not be started: ' . $failure->getMessage());
        }

        return $this->success("Started packaging {$plan->project->name()}.");
    }

    protected function permission(): string
    {
        return 'plugintools.command.makeplugin';
    }

    protected function allowedSenders(): AllowedCommandSenders
    {
        return AllowedCommandSenders::CONSOLE_ONLY;
    }
}
