<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\PluginTools\Packaging\PluginPackager;
use Bedriox\PluginTools\Packaging\PackageException;
use Phar;
use PHPUnit\Framework\TestCase;

final class PluginPackagerTest extends TestCase
{
    public function testBuildsSortedBoundedPackageAndChecksum(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-' . bin2hex(random_bytes(6));
        mkdir($root . '/src', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/plugin.json');
        file_put_contents($root . '/src/Z.php', "<?php\ndeclare(strict_types=1);\n");
        mkdir($root . '/vendor');
        file_put_contents($root . '/vendor/secret.php', 'excluded');
        $output = $root . '/out.phar';
        new PluginPackager()->build($root, $output);
        $phar = new Phar($output);
        self::assertArrayHasKey('plugin.json', $phar);
        self::assertArrayNotHasKey('vendor/secret.php', $phar);
        self::assertSame(hash_file('sha256', $output) . '  out.phar', trim((string) file_get_contents($output . '.sha256')));
    }

    public function testRejectsFileCountOverflow(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-limit-' . bin2hex(random_bytes(6));
        mkdir($root . '/src', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/plugin.json');
        file_put_contents($root . '/src/A.php', '<?php');
        $this->expectException(PackageException::class);
        new PluginPackager(maximumFiles: 1)->build($root, $root . '/out.phar');
    }

    public function testRejectsSymbolicLinksWhenSupported(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-link-' . bin2hex(random_bytes(6));
        mkdir($root . '/src', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/plugin.json');
        file_put_contents($root . '/src/A.php', '<?php');
        if (!@symlink($root . '/src/A.php', $root . '/src/Linked.php')) {
            self::markTestSkipped('Symbolic-link creation is unavailable.');
        }
        $this->expectException(PackageException::class);
        new PluginPackager()->build($root, $root . '/out.phar');
    }

    public function testPackagedPluginToolsCanBuildAnotherPlugin(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-self-' . bin2hex(random_bytes(6));
        mkdir($root . '/project/src', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/project/plugin.json');
        file_put_contents($root . '/project/src/Main.php', "<?php\ndeclare(strict_types=1);\n");
        $tool = $root . '/PluginTools.phar';
        new PluginPackager()->build(dirname(__DIR__), $tool);
        $output = $root . '/Result.phar';
        $process = proc_open(
            [PHP_BINARY, '-d', 'phar.readonly=0', $tool, $root . '/project', $output],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $stdout . $stderr);
        self::assertFileExists($output);
        self::assertFileExists($output . '.sha256');
    }
}
