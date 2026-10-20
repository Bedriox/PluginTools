<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Core;

enum PluginPlatform: string
{
    case BEDRIOX = 'bedriox';
    case GATEWAY = 'gateway';

    /** @return list<string> */
    public function supportedApiConstraints(): array
    {
        return match ($this) {
            self::BEDRIOX => ['0.4', '0.4.0', '^0.4', '^0.4.0', '~0.4', '~0.4.0'],
            self::GATEWAY => ['0.2', '0.2.0', '^0.2', '^0.2.0', '~0.2', '~0.2.0'],
        };
    }

    /** @return list<string> */
    public function supportedLoadPhases(): array
    {
        return match ($this) {
            self::BEDRIOX => ['STARTUP', 'WORLD_READY'],
            self::GATEWAY => ['STARTUP'],
        };
    }
}
