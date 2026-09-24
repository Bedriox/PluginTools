<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandValues
{
    public function has(string $name): bool
    {
        return false;
    }

    public function string(string $name): string
    {
        return '';
    }

    public function choice(string $name): string
    {
        return '';
    }
}
