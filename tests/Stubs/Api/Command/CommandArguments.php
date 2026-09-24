<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final class CommandArguments
{
    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public static function none(): self
    {
        return new self();
    }

    public function addArgument(CommandParameter $parameter): self
    {
        return $this;
    }

    public function addOverload(CommandOverload $overload): self
    {
        return $this;
    }
}
