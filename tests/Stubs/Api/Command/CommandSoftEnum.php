<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

interface CommandSoftEnum
{
    public function name(): string;

    /** @return list<string> */
    public function values(): array;

    /** @param list<string> $values */
    public function replace(array $values): bool;

    public function add(string $value): bool;
    public function remove(string $value): bool;
    public function isRegistered(): bool;
}
