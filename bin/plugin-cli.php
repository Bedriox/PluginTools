<?php

declare(strict_types=1);

use Bedriox\PluginTools\Packaging\PluginPackager;

$archive = Phar::running(false);
spl_autoload_register(static function (string $class) use ($archive): void {
    $prefix = 'Bedriox\\PluginTools\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    if ($relative === '' || preg_match('/^[A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)*$/D', $relative) !== 1) {
        return;
    }
    $path = 'phar://' . $archive . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$arguments = array_slice($argv, 1);
$overwrite = false;
if (($key = array_search('--overwrite', $arguments, true)) !== false) {
    $overwrite = true;
    array_splice($arguments, (int) $key, 1);
}
if (count($arguments) < 1 || count($arguments) > 2) {
    fwrite(STDERR, "Usage: PluginTools.phar <project-directory> [output.phar] [--overwrite]\n");
    exit(2);
}

$project = $arguments[0];
$output = $arguments[1] ?? getcwd() . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . basename($project) . '.phar';
try {
    $name = (new PluginPackager())->build($project, $output, $overwrite);
    fwrite(STDOUT, "Built {$name}: {$output}\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Build failed: ' . $error->getMessage() . "\n");
    exit(1);
}
