<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Development;

use Bedriox\Api\Plugin\Plugin;
use Bedriox\Api\Plugin\PluginContext;
use RuntimeException;
use Throwable;

/** Namespace-restricted loader used only after Bedriox admits a discovered definition. */
final class SourcePluginAutoloader
{
    /** @var callable(string): void */
    private $loader;
    private bool $registered = false;

    public function __construct(private readonly SourcePluginProject $project)
    {
        $prefix = $project->manifestData->namespace . '\\';
        $sourceDirectory = realpath($project->root . DIRECTORY_SEPARATOR . 'src');
        if ($sourceDirectory === false || !is_dir($sourceDirectory) || is_link($project->root . DIRECTORY_SEPARATOR . 'src')) {
            throw new RuntimeException('The source plugin src directory is unsafe.');
        }
        $source = rtrim(str_replace('\\', '/', $sourceDirectory), '/') . '/';
        $this->loader = static function (string $class) use ($prefix, $source): void {
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            if ($relative === '' || preg_match('/^[A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)*$/D', $relative) !== 1) {
                return;
            }
            $path = $source . str_replace('\\', '/', $relative) . '.php';
            $resolved = realpath($path);
            if ($resolved === false || !is_file($resolved) || is_link($path)) {
                return;
            }
            $normalized = str_replace('\\', '/', $resolved);
            $contained = DIRECTORY_SEPARATOR === '\\'
                ? str_starts_with(strtolower($normalized), strtolower($source))
                : str_starts_with($normalized, $source);
            if ($contained) {
                require $resolved;
            }
        };
    }

    public function register(): void
    {
        if (!$this->registered) {
            spl_autoload_register($this->loader, true, true);
            $this->registered = true;
        }
    }

    public function unregister(): void
    {
        if ($this->registered) {
            spl_autoload_unregister($this->loader);
            $this->registered = false;
        }
    }

    public function instantiate(PluginContext $context): Plugin
    {
        $this->register();
        try {
            $main = $this->project->manifestData->main;
            if (!class_exists($main)) {
                throw new RuntimeException("Source plugin entry point {$main} was not found.");
            }
            if (!is_subclass_of($main, Plugin::class)) {
                throw new RuntimeException("Source plugin entry point {$main} must extend " . Plugin::class . '.');
            }

            return new $main($context);
        } catch (Throwable $failure) {
            $this->unregister();
            throw $failure;
        }
    }
}
