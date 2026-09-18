<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

interface CommandJob
{
    public function poll(): bool;
    public function cancel(): void;
}
