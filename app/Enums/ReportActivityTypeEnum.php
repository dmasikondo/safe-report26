<?php

namespace App\Enums;

enum ReportActivityTypeEnum: string
{
    case STATUS_CHANGED = 'status_changed';
    case ASSIGNED = 'assigned';
    case PRIORITY_CHANGED = 'priority_changed';
    case NOTE_ADDED = 'note_added';

    public function label(): string
    {
        return match ($this) {
            self::STATUS_CHANGED => 'Status changed',
            self::ASSIGNED => 'Assignment changed',
            self::PRIORITY_CHANGED => 'Priority changed',
            self::NOTE_ADDED => 'Note added',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::STATUS_CHANGED => 'arrow-path',
            self::ASSIGNED => 'user-plus',
            self::PRIORITY_CHANGED => 'flag',
            self::NOTE_ADDED => 'chat-bubble-left-ellipsis',
        };
    }
}
