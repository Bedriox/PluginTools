<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Development;

final readonly class SourcePluginFailure
{
    public function __construct(
        public string $directory,
        public string $reason,
    ) {}
}
