<?php

declare(strict_types=1);

namespace Bedriox\Gateway\Api\Plugin;

final class PluginLogger
{
    /** @var list<string> */
    public array $infoMessages = [];
    /** @var list<string> */
    public array $warningMessages = [];

    public function info(string $message): void
    {
        $this->infoMessages[] = $message;
    }
    public function warning(string $message): void
    {
        $this->warningMessages[] = $message;
    }
}
