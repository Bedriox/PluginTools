<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Command;

use Bedriox\Api\Command\CommandJob;
use Bedriox\Api\Command\CommandSender;
use Closure;
use RuntimeException;

final class PackageBuildJob implements CommandJob
{
    /** @var resource|null */
    private $process = null;
    /** @var array<int, resource> */
    private array $pipes = [];
    private string $output = '';
    private int $startedAt;
    private bool $finished = false;

    /** @param Closure(): void $onFinished */
    public function __construct(
        private readonly MakePluginPlan $plan,
        private readonly string $toolArchive,
        private readonly CommandSender $sender,
        private readonly Closure $onFinished,
        private readonly int $timeoutSeconds = 30,
        private readonly int $maximumOutputBytes = 16_384,
    ) {
        if ($timeoutSeconds < 1 || $timeoutSeconds > 300 || $maximumOutputBytes < 1024 || $maximumOutputBytes > 65_536) {
            throw new \InvalidArgumentException('Build process limits are invalid.');
        }
        $this->startedAt = hrtime(true);
        $command = [PHP_BINARY, '-d', 'phar.readonly=0', $toolArchive, $plan->project->root, $plan->output];
        if ($plan->overwrite) {
            $command[] = '--overwrite';
        }
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('The packaging worker could not be started.');
        }
        $this->process = $process;
        /** @var array<int, resource> $pipes */
        $this->pipes = $pipes;
        fclose($this->pipes[0]);
        unset($this->pipes[0]);
        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }
    }

    public function poll(): bool
    {
        if ($this->finished) {
            return true;
        }
        $this->drain();
        if ((hrtime(true) - $this->startedAt) / 1_000_000_000 > $this->timeoutSeconds) {
            $this->finish(false, 'Packaging timed out.');
            return true;
        }
        if (!is_resource($this->process)) {
            $this->finish(false, 'Packaging worker was unavailable.');
            return true;
        }
        $status = proc_get_status($this->process);
        if ($status['running']) {
            return false;
        }
        $this->drain();
        $exitCode = $status['exitcode'];
        $message = trim($this->output);
        $this->close();
        if ($exitCode !== 0) {
            $this->finish(false, $message === '' ? 'Packaging failed.' : $message);
            return true;
        }
        $hash = is_file($this->plan->output) ? hash_file('sha256', $this->plan->output) : false;
        $size = is_file($this->plan->output) ? filesize($this->plan->output) : false;
        if ($hash === false || $size === false) {
            $this->finish(false, 'Packaging worker did not publish a complete archive.');
            return true;
        }
        $seconds = (hrtime(true) - $this->startedAt) / 1_000_000_000;
        $this->finish(true, sprintf(
            'Built %s.phar (%d bytes, SHA-256 %s) in %.2f seconds.',
            $this->plan->project->name(),
            $size,
            $hash,
            $seconds,
        ));
        return true;
    }

    public function cancel(): void
    {
        if ($this->finished) {
            return;
        }
        if (is_resource($this->process)) {
            proc_terminate($this->process);
        }
        $this->close();
        $this->finished = true;
        ($this->onFinished)();
    }

    private function drain(): void
    {
        foreach ($this->pipes as $pipe) {
            if (strlen($this->output) >= $this->maximumOutputBytes) {
                break;
            }
            $chunk = stream_get_contents($pipe, $this->maximumOutputBytes - strlen($this->output));
            if (is_string($chunk)) {
                $this->output .= $chunk;
            }
        }
    }

    private function finish(bool $success, string $message): void
    {
        if ($this->finished) {
            return;
        }
        if (!$success && is_resource($this->process)) {
            proc_terminate($this->process);
        }
        $this->close();
        $this->finished = true;
        $this->sender->sendMessage($message);
        ($this->onFinished)();
    }

    private function close(): void
    {
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        $this->pipes = [];
        if (is_resource($this->process)) {
            proc_close($this->process);
        }
        $this->process = null;
    }
}
