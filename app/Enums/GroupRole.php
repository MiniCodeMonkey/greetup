<?php

namespace App\Enums;

enum GroupRole: string
{
    case Member = 'member';
    case EventOrganizer = 'event_organizer';
    case AssistantOrganizer = 'assistant_organizer';
    case CoOrganizer = 'co_organizer';
    case Organizer = 'organizer';

    /**
     * Get the numeric level for role hierarchy comparison.
     */
    public function level(): int
    {
        return match ($this) {
            self::Member => 0,
            self::EventOrganizer => 1,
            self::AssistantOrganizer => 2,
            self::CoOrganizer => 3,
            self::Organizer => 4,
        };
    }

    /**
     * Check if this role meets or exceeds the given minimum role.
     */
    public function isAtLeast(self $minimumRole): bool
    {
        return $this->level() >= $minimumRole->level();
    }
}
