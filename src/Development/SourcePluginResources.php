<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Development;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class SourcePluginResources
{
    private const int MAXIMUM_FILES = 2_048;
    private const int MAXIMUM_FILE_BYTES = 8_388_608;
    private const int MAXIMUM_TOTAL_BYTES = 67_108_864;

    /** @return array<string, string> */
    public function read(SourcePluginProject $project): array
    {
        $directory = $project->root . DIRECTORY_SEPARATOR . 'resources';
        if (!file_exists($directory)) {
            return [];
        }
        $root = realpath($directory);
        if ($root === false || !is_dir($root) || is_link($directory)) {
            throw new RuntimeException('The source plugin resources directory is unsafe.');
        }
        $prefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $resources = [];
        $total = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile()) {
                continue;
            }
            if ($entry->isLink() || count($resources) >= self::MAXIMUM_FILES) {
                throw new RuntimeException('Source plugin resource limits were exceeded.');
            }
            $resolved = realpath($entry->getPathname());
            $size = $entry->getSize();
            if ($resolved === false || $size > self::MAXIMUM_FILE_BYTES || ($total += $size) > self::MAXIMUM_TOTAL_BYTES) {
                throw new RuntimeException('Source plugin resource limits were exceeded.');
            }
            $normalized = str_replace('\\', '/', $resolved);
            $contained = DIRECTORY_SEPARATOR === '\\'
                ? str_starts_with(strtolower($normalized), strtolower($prefix))
                : str_starts_with($normalized, $prefix);
            if (!$contained) {
                throw new RuntimeException('A source plugin resource escapes its resources directory.');
            }
            $name = substr($normalized, strlen($prefix));
            if ($name === '' || strlen($name) > 512 || str_contains($name, "\0")) {
                throw new RuntimeException('A source plugin resource path is invalid.');
            }
            $contents = file_get_contents($resolved);
            if (!is_string($contents)) {
                throw new RuntimeException("Source plugin resource {$name} could not be read.");
            }
            $resources[$name] = $contents;
        }
        ksort($resources, SORT_STRING);

        return $resources;
    }
}
