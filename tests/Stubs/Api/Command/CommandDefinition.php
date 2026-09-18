<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandDefinition
{
    /** @param list<string> $aliases */
    public function __construct(
        public string $name,
        public string $description,
        public string $usage,
        public array $aliases = [],
        public ?string $permission = null,
        public AllowedCommandSenders $allowedSenders = AllowedCommandSenders::ANY,
    ) {}
}
