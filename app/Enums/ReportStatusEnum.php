<?php

namespace App\Enums;

enum ReportStatusEnum: string
{
    case SUBMITTED = 'submitted';
    case RECEIVED = 'received';
    case UNDER_REVIEW = 'under_review';
    case INVESTIGATING = 'investigating';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::RECEIVED => 'Received',
            self::UNDER_REVIEW => 'Under Review',
            self::INVESTIGATING => 'Investigating',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUBMITTED => 'zinc',
            self::RECEIVED => 'blue',
            self::UNDER_REVIEW => 'amber',
            self::INVESTIGATING => 'violet',
            self::RESOLVED => 'green',
            self::CLOSED => 'zinc',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::RESOLVED, self::CLOSED]);
    }

    /**
     * Statuses a case may move to from this status.
     *
     * Forward steps follow the timeline; any open case may be closed
     * (dismissed without resolution); resolved or closed cases may be
     * reopened for further work.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SUBMITTED => [self::RECEIVED, self::CLOSED],
            self::RECEIVED => [self::UNDER_REVIEW, self::CLOSED],
            self::UNDER_REVIEW => [self::INVESTIGATING, self::CLOSED],
            self::INVESTIGATING => [self::RESOLVED, self::CLOSED],
            self::RESOLVED => [self::INVESTIGATING],
            self::CLOSED => [self::UNDER_REVIEW],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }

    /**
     * Closing a case or reopening a finished one is a supervisory decision;
     * investigators may only move their own cases forward.
     */
    public function transitionRequiresSupervisor(self $target): bool
    {
        return $target === self::CLOSED || ! $this->isActive();
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
