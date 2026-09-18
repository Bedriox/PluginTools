<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

interface CommandRegistrar
{
    public function register(CommandDefinition $definition, callable $handler): mixed;
    public function submitJob(CommandJob $job): mixed;
}
