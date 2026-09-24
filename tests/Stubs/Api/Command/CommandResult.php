<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

final readonly class CommandResult
{
    private function __construct(private bool $successful, private ?string $message) {}

    public static function success(?string $message = null): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public function isSuccess(): bool
    {
        return $this->successful;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
