<?php

namespace App\Enums;

enum ReportStatusEnum: string
{
    case SUBMITTED   = 'submitted';
    case RECEIVED    = 'received';
    case UNDER_REVIEW = 'under_review';
    case INVESTIGATING = 'investigating';
    case RESOLVED    = 'resolved';
    case CLOSED      = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED     => 'Submitted',
            self::RECEIVED      => 'Received',
            self::UNDER_REVIEW  => 'Under Review',
            self::INVESTIGATING => 'Investigating',
            self::RESOLVED      => 'Resolved',
            self::CLOSED        => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUBMITTED     => 'zinc',
            self::RECEIVED      => 'blue',
            self::UNDER_REVIEW  => 'amber',
            self::INVESTIGATING => 'violet',
            self::RESOLVED      => 'green',
            self::CLOSED        => 'zinc',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::RESOLVED, self::CLOSED]);
    }

    /** Ordered steps for the timeline (all statuses in workflow order). */
    public static function timeline(): array
    {
        return [
            self::SUBMITTED,
            self::RECEIVED,
            self::UNDER_REVIEW,
            self::INVESTIGATING,
            self::RESOLVED,
        ];
    }
}
