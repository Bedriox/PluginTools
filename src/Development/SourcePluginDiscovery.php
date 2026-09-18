<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Development;

use Bedriox\PluginTools\Packaging\ManifestValidator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/** Discovers direct source-project children without executing plugin code. */
final class SourcePluginDiscovery
{
    public function __construct(
        private readonly int $maximumProjects = 64,
        private readonly int $maximumFiles = 4096,
        private readonly int $maximumFileBytes = 16_777_216,
        private readonly int $maximumTotalBytes = 67_108_864,
    ) {
        if ($this->maximumProjects < 0 || $this->maximumProjects > 256) {
            throw new \InvalidArgumentException('Source plugin limit must be between 0 and 256.');
        }
    }

    public function discover(string $pluginsDirectory): SourcePluginDiscoveryResult
    {
        $root = realpath($pluginsDirectory);
        if ($root === false || !is_dir($root) || is_link($pluginsDirectory)) {
            throw new \InvalidArgumentException('Plugins directory must be a regular directory.');
        }

        $directories = [];
        foreach (new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isDir()) {
                $directories[] = $entry->getPathname();
            }
        }
        natcasesort($directories);
        $directories = array_values($directories);
        if (count($directories) > $this->maximumProjects) {
            throw new \InvalidArgumentException('Source plugin directory count exceeds the configured limit.');
        }

        $projects = [];
        $failures = [];
        $names = [];
        foreach ($directories as $directory) {
            $label = $this->safeLabel(basename($directory));
            try {
                if (is_link($directory)) {
                    throw new \RuntimeException('Symbolic-link project directories are not permitted.');
                }
                $resolved = realpath($directory);
                if ($resolved === false || !$this->within($root, $resolved)) {
                    throw new \RuntimeException('Source plugin path escapes the plugins directory.');
                }
                $manifest = $resolved . DIRECTORY_SEPARATOR . 'plugin.json';
                $manifestData = new ManifestValidator()->read($manifest);
                $name = $manifestData->name;
                $this->validateTree($resolved, $manifestData->namespace, $manifestData->main);
                $key = strtolower($name);
                if (isset($names[$key])) {
                    throw new \RuntimeException("Duplicate source plugin name: {$name}.");
                }
                $names[$key] = true;
                $projects[] = new SourcePluginProject($manifestData, $resolved, $manifest);
            } catch (Throwable $failure) {
                $failures[] = new SourcePluginFailure($label, $failure->getMessage());
            }
        }

        return new SourcePluginDiscoveryResult($projects, $failures);
    }

    private function within(string $root, string $candidate): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $candidate = str_replace('\\', '/', $candidate);
        if (DIRECTORY_SEPARATOR === '\\') {
            $root = strtolower($root);
            $candidate = strtolower($candidate);
        }

        return str_starts_with($candidate, $root);
    }

    private function validateTree(string $root, string $namespace, string $main): void
    {
        $count = 0;
        $total = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }
            if ($entry->isLink()) {
                throw new \RuntimeException('Symbolic links are not permitted in source plugins.');
            }
            if (!$entry->isFile()) {
                continue;
            }
            $resolved = realpath($entry->getPathname());
            $size = $entry->getSize();
            if ($resolved === false || !$this->within($root, $resolved)) {
                throw new \RuntimeException('A source file escapes the plugin project.');
            }
            if ($size > $this->maximumFileBytes || ++$count > $this->maximumFiles || ($total += $size) > $this->maximumTotalBytes) {
                throw new \RuntimeException('Source plugin limits exceeded.');
            }
        }

        $prefix = $namespace . '\\';
        if (!str_starts_with($main, $prefix)) {
            throw new \RuntimeException('The source plugin entry point must be inside its namespace.');
        }
        $relative = substr($main, strlen($prefix));
        $entryPoint = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (!is_file($entryPoint) || is_link($entryPoint)) {
            throw new \RuntimeException('The source plugin entry point file was not found.');
        }
    }

    private function safeLabel(string $label): string
    {
        $label = preg_replace('/[^A-Za-z0-9_.-]/', '_', $label) ?? 'unknown';
        return substr($label, 0, 80);
    }
}
