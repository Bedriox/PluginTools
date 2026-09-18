<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Command;

use Bedriox\PluginTools\Development\SourcePluginProject;

final readonly class MakePluginPlan
{
    public function __construct(
        public SourcePluginProject $project,
        public string $output,
        public bool $overwrite,
    ) {}
}
