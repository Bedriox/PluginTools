<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandOverload
{
    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function addArgument(CommandParameter $parameter): self
    {
        return $this;
    }
}
