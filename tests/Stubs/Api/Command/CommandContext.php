<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandContext
{
    public function values(): CommandValues
    {
        return new CommandValues();
    }
    public function sender(): CommandSender
    {
        throw new \LogicException('Test stub.');
    }
}
