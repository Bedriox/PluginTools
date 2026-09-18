<?php

declare(strict_types=1);

namespace Bedriox\Api\Command;

enum AllowedCommandSenders
{
    case ANY;
    case CONSOLE_ONLY;
    case PLAYER_ONLY;
}
