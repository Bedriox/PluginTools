<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Packaging;

use FilesystemIterator;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class PluginPackager
{
    public function __construct(private readonly int $maximumFiles = 4096, private readonly int $maximumFileBytes = 16_777_216, private readonly int $maximumTotalBytes = 67_108_864) {}

    public function build(string $project, string $output): string
    {
        if (ini_get('phar.readonly') !== '0') {
            throw new PackageException('Building requires PHP with phar.readonly=0.');
        }
        $root = realpath($project);
        if ($root === false || !is_dir($root) || is_link($project)) {
            throw new PackageException('Project must be a regular directory.');
        }
        $pluginName = new ManifestValidator()->validate($root . DIRECTORY_SEPARATOR . 'plugin.json');
        $files = $this->collect($root);
        $outputDirectory = dirname($output);
        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0o775, true) && !is_dir($outputDirectory)) {
            throw new PackageException('Output directory could not be created.');
        }
        $temporary = $output . '.tmp.phar';
        if (file_exists($temporary) && !unlink($temporary)) {
            throw new PackageException('Temporary package could not be replaced.');
        }
        try {
            $phar = new Phar($temporary, 0, basename($output));
            $phar->startBuffering();
            foreach ($files as $relative => $absolute) {
                $contents = file_get_contents($absolute);
                if ($contents === false) {
                    throw new PackageException("Could not read {$relative}.");
                }
                $phar->addFromString($relative, $contents);
            }
            $alias = basename($output);
            $phar->setStub(
                "<?php\n"
                . 'Phar::mapPhar(' . var_export($alias, true) . ");\n"
                . "if (PHP_SAPI === 'cli') {\n"
                . "    \$cli = 'phar://' . " . var_export($alias, true) . " . '/bin/plugin-cli.php';\n"
                . "    if (!is_file(\$cli)) { fwrite(STDERR, \"This plugin PHAR has no standalone command.\\n\"); exit(2); }\n"
                . "    require \$cli;\n"
                . "}\n"
                . "__HALT_COMPILER();",
            );
            $phar->setSignatureAlgorithm(Phar::SHA256);
            $phar->stopBuffering();
            unset($phar);
            if (file_exists($output) && !unlink($output)) {
                throw new PackageException('Existing output could not be replaced.');
            }
            if (!rename($temporary, $output)) {
                throw new PackageException('Package could not be moved into place.');
            }
            $hash = hash_file('sha256', $output);
            if ($hash === false || file_put_contents($output . '.sha256', $hash . '  ' . basename($output) . "\n", LOCK_EX) === false) {
                throw new PackageException('SHA-256 sidecar could not be written.');
            }
            return $pluginName;
        } finally {
            if (file_exists($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /** @return array<string, string> */
    private function collect(string $root): array
    {
        $result = [];
        $count = 0;
        $total = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }
            $path = $entry->getPathname();
            if ($entry->isLink()) {
                throw new PackageException('Symbolic links are not permitted.');
            }
            if (!$entry->isFile()) {
                continue;
            }
            $resolved = realpath($path);
            if ($resolved === false || !$this->within($root, $resolved)) {
                throw new PackageException('A source path escapes the project root.');
            }
            $relative = str_replace('\\', '/', substr($resolved, strlen($root) + 1));
            if ($this->excluded($relative)) {
                continue;
            }
            $size = $entry->getSize();
            if ($size > $this->maximumFileBytes || ++$count > $this->maximumFiles || ($total += $size) > $this->maximumTotalBytes) {
                throw new PackageException('Package limits exceeded.');
            }
            $result[$relative] = $resolved;
        }
        $sources = array_filter($result, static fn(string $key): bool => str_starts_with($key, 'src/') && str_ends_with($key, '.php'), ARRAY_FILTER_USE_KEY);
        if (!isset($result['plugin.json']) || $sources === []) {
            throw new PackageException('Package requires plugin.json and PHP sources under src/.');
        }
        ksort($result, SORT_STRING);
        return $result;
    }

    private function excluded(string $path): bool
    {
        return preg_match('~(^|/)(?:\.git|\.github|vendor|tests?|build|dist|coverage|\.idea|\.vscode)(?:/|$)|(?:^|/)(?:\.env(?:\..*)?|.*\.(?:key|pem|p12|pfx|log))$~i', $path) === 1;
    }
    private function within(string $root, string $path): bool
    {
        $root = strtolower(str_replace('\\', '/', rtrim($root, '\\/')) . '/');
        return str_starts_with(strtolower(str_replace('\\', '/', $path)), $root);
    }
}
