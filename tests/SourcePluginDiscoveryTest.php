<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\PluginTools\Command\MakePluginPlanner;
use Bedriox\PluginTools\Development\SourcePluginAutoloader;
use Bedriox\PluginTools\Development\SourcePluginDiscovery;
use Bedriox\PluginTools\Development\SourcePluginResources;
use Bedriox\Api\Plugin\PluginContext;
use PHPUnit\Framework\TestCase;

final class SourcePluginDiscoveryTest extends TestCase
{
    public function testDiscoversOnlyValidatedDirectSourceProjectsAndPlansNamedBuild(): void
    {
        $root = $this->temporary('discovery');
        $this->writePlugin($root . '/Alpha', 'Alpha');
        mkdir($root . '/Unrelated');

        $result = new SourcePluginDiscovery()->discover($root);

        self::assertCount(1, $result->projects);
        self::assertSame('Alpha', $result->projects[0]->name());
        self::assertCount(1, $result->failures);
        self::assertSame('Unrelated', $result->failures[0]->directory);

        $plan = new MakePluginPlanner()->plan(['Alpha', '--overwrite'], $result, $root . '/data');
        self::assertSame($result->projects[0], $plan->project);
        self::assertSame(realpath($root . '/data') . DIRECTORY_SEPARATOR . 'Alpha.phar', $plan->output);
        self::assertTrue($plan->overwrite);
    }

    public function testPlannerRejectsPathsAndUnknownNames(): void
    {
        $root = $this->temporary('planner');
        $result = new SourcePluginDiscovery()->discover($root);

        $this->expectException(\InvalidArgumentException::class);
        new MakePluginPlanner()->plan(['../Alpha'], $result, $root . '/data');
    }

    public function testRejectsMissingEntryPointBeforeAnyCodeExecutes(): void
    {
        $root = $this->temporary('entry');
        $this->writePlugin($root . '/Alpha', 'Alpha');
        unlink($root . '/Alpha/src/Main.php');

        $result = new SourcePluginDiscovery()->discover($root);

        self::assertSame([], $result->projects);
        self::assertCount(1, $result->failures);
        self::assertStringContainsString('entry point', $result->failures[0]->reason);
    }

    public function testNamespaceRestrictedLoaderInstantiatesAdmittedProject(): void
    {
        $root = $this->temporary('loader');
        $this->writePlugin($root . '/Alpha', 'Alpha', true);
        $project = new SourcePluginDiscovery()->discover($root)->projects[0];
        $loader = new SourcePluginAutoloader($project);

        $plugin = $loader->instantiate(new PluginContext());

        self::assertSame('Example\\Alpha\\Main', $plugin::class);
        $loader->unregister();
    }

    public function testReadsBoundedResourcesWithPortableRelativeNames(): void
    {
        $root = $this->temporary('resources');
        $this->writePlugin($root . '/Alpha', 'Alpha');
        mkdir($root . '/Alpha/resources/templates', 0o775, true);
        file_put_contents($root . '/Alpha/resources/config.yml', "enabled: true\n");
        file_put_contents($root . '/Alpha/resources/templates/welcome.txt', 'Welcome');
        $project = new SourcePluginDiscovery()->discover($root)->projects[0];

        self::assertSame([
            'config.yml' => "enabled: true\n",
            'templates/welcome.txt' => 'Welcome',
        ], new SourcePluginResources()->read($project));
    }

    private function temporary(string $label): string
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-' . $label . '-' . bin2hex(random_bytes(6));
        mkdir($root, 0o775, true);
        return $root;
    }

    private function writePlugin(string $root, string $name, bool $executable = false): void
    {
        mkdir($root . '/src', 0o775, true);
        $manifest = [
            'schema' => 1,
            'name' => $name,
            'version' => '0.1.0',
            'api' => '^0.4',
            'main' => "Example\\{$name}\\Main",
            'namespace' => "Example\\{$name}",
            'authors' => ['Test'],
            'dependencies' => [],
            'softDependencies' => [],
            'load' => 'STARTUP',
        ];
        file_put_contents($root . '/plugin.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $source = "<?php\ndeclare(strict_types=1);\n";
        if ($executable) {
            $source .= "namespace Example\\{$name};\nfinal class Main extends \\Bedriox\\Api\\Plugin\\Plugin {}\n";
        }
        file_put_contents($root . '/src/Main.php', $source);
    }
}
