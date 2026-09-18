<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Command;

use Bedriox\PluginTools\Development\SourcePluginDiscoveryResult;
use InvalidArgumentException;
use RuntimeException;

final class MakePluginPlanner
{
    /** @param list<string> $arguments */
    public function plan(array $arguments, SourcePluginDiscoveryResult $catalog, string $pluginToolsDataFolder): MakePluginPlan
    {
        if (count($arguments) < 1 || count($arguments) > 2 || ($arguments[1] ?? '--overwrite') !== '--overwrite') {
            throw new InvalidArgumentException('Usage: makeplugin <PluginName> [--overwrite]');
        }
        $name = $arguments[0];
        if (preg_match('/^[A-Z][A-Za-z0-9_]{0,63}$/D', $name) !== 1) {
            throw new InvalidArgumentException('PluginName must be a discovered plugin name, not a path.');
        }
        $project = $catalog->find($name);
        if ($project === null) {
            throw new InvalidArgumentException("Source plugin {$name} was not discovered.");
        }
        if (!is_dir($pluginToolsDataFolder)
            && !mkdir($pluginToolsDataFolder, 0o775, true)
            && !is_dir($pluginToolsDataFolder)) {
            throw new RuntimeException('PluginTools data directory could not be created.');
        }
        $root = realpath($pluginToolsDataFolder);
        if ($root === false || is_link($pluginToolsDataFolder)) {
            throw new RuntimeException('PluginTools data directory is unsafe.');
        }

        return new MakePluginPlan(
            $project,
            $root . DIRECTORY_SEPARATOR . $project->name() . '.phar',
            count($arguments) === 2,
        );
    }
}
