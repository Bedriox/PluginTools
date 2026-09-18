<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Development;

final readonly class SourcePluginDiscoveryResult
{
    /**
     * @param list<SourcePluginProject> $projects
     * @param list<SourcePluginFailure> $failures
     */
    public function __construct(
        public array $projects,
        public array $failures,
    ) {}

    public function find(string $name): ?SourcePluginProject
    {
        foreach ($this->projects as $project) {
            if (strcasecmp($project->name(), $name) === 0) {
                return $project;
            }
        }

        return null;
    }
}
