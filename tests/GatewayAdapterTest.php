<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\Gateway\Api\Plugin\PluginContext;
use Bedriox\Gateway\Api\Plugin\PluginLogger;
use Bedriox\Gateway\Api\Plugin\SourcePluginDefinition;
use Bedriox\Gateway\Api\Plugin\SourcePluginRegistrar;
use Bedriox\PluginTools\Host\Gateway\PluginTools;
use PHPUnit\Framework\TestCase;

final class GatewayAdapterTest extends TestCase
{
    public function testLogsSafeReasonWhenSourcePluginIsRejected(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-gateway-' . bin2hex(random_bytes(6));
        mkdir($root . '/Broken/src', 0o775, true);
        file_put_contents($root . '/Broken/plugin.json', json_encode([
            'schema' => 2,
            'name' => 'Broken',
        ], JSON_THROW_ON_ERROR));
        file_put_contents($root . '/Broken/src/Main.php', '<?php');
        $logger = new PluginLogger();
        $registrar = new RecordingSourcePluginRegistrar($root);

        $plugin = new PluginTools(new PluginContext($logger, $registrar));
        $plugin->onLoad();

        self::assertSame([], $registrar->definitions);
        self::assertCount(1, $logger->warningMessages);
        self::assertStringContainsString('Source plugin Broken was rejected:', $logger->warningMessages[0]);
        self::assertStringContainsString('fields do not match schema 2', $logger->warningMessages[0]);
    }

    public function testRegistersGatewayDefinitionWithBoundedResources(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-tools-gateway-' . bin2hex(random_bytes(6));
        mkdir($root . '/Welcome/src', 0o775, true);
        mkdir($root . '/Welcome/resources', 0o775, true);
        file_put_contents($root . '/Welcome/plugin.json', json_encode([
            'schema' => 2,
            'platform' => 'gateway',
            'name' => 'Welcome',
            'version' => '1.0.0',
            'api' => '^0.2',
            'main' => 'Example\\Welcome\\Main',
            'namespace' => 'Example\\Welcome',
            'authors' => ['Test'],
            'dependencies' => [],
            'softDependencies' => [],
            'load' => 'STARTUP',
        ], JSON_THROW_ON_ERROR));
        file_put_contents($root . '/Welcome/src/Main.php', '<?php');
        file_put_contents($root . '/Welcome/resources/config.yml', "message: Welcome\n");
        $logger = new PluginLogger();
        $registrar = new RecordingSourcePluginRegistrar($root);

        $plugin = new PluginTools(new PluginContext($logger, $registrar));
        $plugin->onLoad();

        self::assertCount(1, $registrar->definitions);
        self::assertSame('gateway', $registrar->definitions[0]->platform);
        self::assertSame(['config.yml' => "message: Welcome\n"], $registrar->definitions[0]->resources);
    }
}

final class RecordingSourcePluginRegistrar implements SourcePluginRegistrar
{
    /** @var list<SourcePluginDefinition> */
    public array $definitions = [];

    public function __construct(private readonly string $directory) {}

    public function pluginsDirectory(): string
    {
        return $this->directory;
    }

    public function register(array $definitions): void
    {
        $this->definitions = $definitions;
    }
}
