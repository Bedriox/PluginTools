<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\Api\Command\CommandSender;
use Bedriox\Api\Command\CommandSenderType;
use Bedriox\PluginTools\Command\MakePluginPlan;
use Bedriox\PluginTools\Command\PackageBuildJob;
use Bedriox\PluginTools\Development\SourcePluginDiscovery;
use Bedriox\PluginTools\Packaging\PluginPackager;
use Closure;
use PHPUnit\Framework\TestCase;

final class PackageBuildJobTest extends TestCase
{
    public function testBuildRunsAsCooperativelyPolledChildProcess(): void
    {
        $root = sys_get_temp_dir() . '/bedriox-plugin-job-' . bin2hex(random_bytes(6));
        mkdir($root . '/project/src', 0o775, true);
        copy(__DIR__ . '/../plugin.json', $root . '/project/plugin.json');
        file_put_contents($root . '/project/src/PluginTools.php', "<?php\ndeclare(strict_types=1);\n");
        $project = new SourcePluginDiscovery()->discover($root)->projects[0];
        $tool = $root . '/PluginTools.phar';
        new PluginPackager()->build(dirname(__DIR__), $tool);
        $sender = new RecordingSender();
        $finished = false;
        $job = new PackageBuildJob(
            new MakePluginPlan($project, $root . '/Result.phar', false),
            $tool,
            $sender,
            Closure::fromCallable(static function () use (&$finished): void {
                $finished = true;
            }),
        );

        $complete = false;
        for ($attempt = 0; $attempt < 500 && !$complete; ++$attempt) {
            $complete = $job->poll();
            if (!$complete) {
                usleep(10_000);
            }
        }

        self::assertTrue($complete);
        self::assertTrue($finished);
        self::assertFileExists($root . '/Result.phar');
        self::assertStringContainsString('Built PluginTools.phar', implode("\n", $sender->messages));
    }
}

final class RecordingSender implements CommandSender
{
    /** @var list<string> */
    public array $messages = [];

    public function sendMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    public function type(): CommandSenderType
    {
        return CommandSenderType::CONSOLE;
    }
    public function name(): string
    {
        return 'CONSOLE';
    }
    public function hasPermission(string $permission): bool
    {
        return true;
    }
}
