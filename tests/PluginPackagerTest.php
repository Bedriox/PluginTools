<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\PluginTools\Packaging\PluginPackager;
use Bedriox\PluginTools\Packaging\PluginToolsArtifactBuilder;
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

    public function testPackageWritesResolvedSchemaAndPlatformWhenSourceOmitsThem(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-normalized-' . bin2hex(random_bytes(6));
        mkdir($root . '/src', 0o775, true);
        $manifest = json_decode((string) file_get_contents(__DIR__ . '/../plugin.json'), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        unset($manifest['schema'], $manifest['platform']);
        file_put_contents($root . '/plugin.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        file_put_contents($root . '/src/Main.php', "<?php\ndeclare(strict_types=1);\n");

        $output = $root . '/out.phar';
        new PluginPackager()->build($root, $output);
        $packaged = json_decode((string) file_get_contents('phar://' . $output . '/plugin.json'), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($packaged);
        self::assertSame(2, $packaged['schema']);
        self::assertSame('bedriox', $packaged['platform']);
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

    public function testPackagedPluginToolsCanBuildBedrioxAndGatewayPlugins(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-self-' . bin2hex(random_bytes(6));
        $tool = $root . '/PluginTools.phar';
        mkdir($root, 0o775, true);
        new PluginPackager()->build(dirname(__DIR__), $tool);
        foreach ([
            'bedriox' => __DIR__ . '/../plugin.json',
            'gateway' => __DIR__ . '/../platforms/gateway/plugin.json',
        ] as $platform => $manifest) {
            $project = $root . '/' . $platform;
            mkdir($project . '/src', 0o775, true);
            copy($manifest, $project . '/plugin.json');
            file_put_contents($project . '/src/Main.php', "<?php\ndeclare(strict_types=1);\n");
            $output = $root . '/' . ucfirst($platform) . '.phar';
            $process = proc_open(
                [PHP_BINARY, '-d', 'phar.readonly=0', $tool, $project, $output],
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
            $packaged = json_decode((string) file_get_contents('phar://' . $output . '/plugin.json'), true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($packaged);
            self::assertSame($platform, $packaged['platform']);
        }
    }

    public function testBuildsOneBedrioxAndGatewayHostArtifact(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-hosts-' . bin2hex(random_bytes(6));
        mkdir($root, 0o775, true);
        $builder = new PluginToolsArtifactBuilder();
        $output = $root . '/PluginTools.phar';

        $builder->build(dirname(__DIR__), $output);

        $bedriox = json_decode((string) file_get_contents('phar://' . $output . '/plugin.json'), true, 16, JSON_THROW_ON_ERROR);
        $gateway = json_decode((string) file_get_contents('phar://' . $output . '/' . PluginPackager::GATEWAY_MANIFEST), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($bedriox);
        self::assertIsArray($gateway);
        self::assertArrayHasKey('platform', $bedriox);
        self::assertArrayHasKey('main', $bedriox);
        self::assertArrayHasKey('platform', $gateway);
        self::assertArrayHasKey('main', $gateway);
        self::assertSame('bedriox', $bedriox['platform']);
        self::assertSame('Bedriox\\PluginTools\\Host\\Bedriox\\PluginTools', $bedriox['main']);
        self::assertSame('gateway', $gateway['platform']);
        self::assertSame('Bedriox\\PluginTools\\Host\\Gateway\\PluginTools', $gateway['main']);
        self::assertSame($bedriox['name'], $gateway['name']);
        self::assertSame($bedriox['version'], $gateway['version']);
        self::assertArrayHasKey(PluginPackager::GATEWAY_MANIFEST, new Phar($output));
        self::assertFileExists($output . '.sha256');
    }

    public function testRejectsMismatchedSharedPluginIdentity(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-shared-mismatch-' . bin2hex(random_bytes(6));
        mkdir($root . '/src', 0o775, true);
        mkdir($root . '/platforms/gateway', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/plugin.json');
        $gateway = json_decode((string) file_get_contents(__DIR__ . '/../platforms/gateway/plugin.json'), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($gateway);
        $gateway['version'] = '9.9.9';
        file_put_contents($root . '/platforms/gateway/plugin.json', json_encode($gateway, JSON_THROW_ON_ERROR));
        file_put_contents($root . '/src/Main.php', "<?php\ndeclare(strict_types=1);\n");

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('version');
        new PluginPackager()->build($root, $root . '/PluginTools.phar');
    }

    public function testRefusesExistingOutputUnlessOverwriteWasRequested(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-overwrite-' . bin2hex(random_bytes(6));
        mkdir($root . '/src', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/plugin.json');
        file_put_contents($root . '/src/Main.php', "<?php\ndeclare(strict_types=1);\n");
        $output = $root . '/out.phar';
        file_put_contents($output, 'preserve-me');

        try {
            new PluginPackager()->build($root, $output);
            self::fail('Existing output should have been rejected.');
        } catch (PackageException $failure) {
            self::assertStringContainsString('--overwrite', $failure->getMessage());
        }
        self::assertSame('preserve-me', file_get_contents($output));

        new PluginPackager()->build($root, $output, true);
        self::assertNotSame('preserve-me', file_get_contents($output));
        self::assertFileExists($output . '.sha256');
    }
}
