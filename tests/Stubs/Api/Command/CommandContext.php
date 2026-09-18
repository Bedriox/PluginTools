<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandContext
{
    /** @return list<string> */
    public function arguments(): array
    {
        return [];
    }
    public function sender(): CommandSender
    {
        throw new \LogicException('Test stub.');
    }
}
