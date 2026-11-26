<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;

/**
 * Notifies the investigator a case went to, and their supervisor, unless
 * that supervisor made the assignment themselves.
 */
class NotifyCaseAssignment
{
    public function handle(Report $report, User $assignee, ?User $actor): void
    {
        $notification = new CaseAssignedNotification($report, $assignee, automatic: $actor === null);

        if ($assignee->isNot($actor)) {
            $assignee->notify($notification);
        }

        $supervisor = $assignee->supervisor;

        if ($supervisor !== null && $supervisor->is_active && $supervisor->isNot($actor)) {
            $supervisor->notify($notification);
        }
    }
}
