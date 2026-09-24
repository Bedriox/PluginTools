<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandParameter
{
    private function __construct() {}

    public static function softEnum(string $name, CommandSoftEnum $softEnum): self
    {
        return new self();
    }

    /** @param list<string> $choices */
    public static function choice(string $name, array $choices): self
    {
        return new self();
    }

    public static function literal(string $literal, ?string $name = null): self
    {
        return new self();
    }

    public function optional(mixed $default = null): self
    {
        return $this;
    }
}
