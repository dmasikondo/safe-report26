<?php

namespace App\Policies;

use App\Enums\ReportStatusEnum;
use App\Models\Report;
use App\Models\User;

/**
 * Case-handling permissions for investigating staff.
 *
 * Supervisors (and admins/superadmins, who inherit supervisor rights)
 * see and manage every case. Investigators see and work only the cases
 * assigned to them.
 */
class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSupervisor() || $user->isInvestigator();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->isSupervisor() || $report->isAssignedTo($user);
    }

    /**
     * Assigning and reassigning cases is a supervisory task.
     */
    public function assign(User $user, Report $report): bool
    {
        return $user->isSupervisor();
    }

    public function changePriority(User $user, Report $report): bool
    {
        return $user->isSupervisor();
    }

    /**
     * Supervisors may make any valid transition. The assigned investigator
     * may move the case forward but not close or reopen it.
     */
    public function changeStatus(User $user, Report $report, ReportStatusEnum $target): bool
    {
        if (! $report->status->canTransitionTo($target)) {
            return false;
        }

        if ($user->isSupervisor()) {
            return true;
        }

        return $report->isAssignedTo($user)
            && ! $report->status->transitionRequiresSupervisor($target);
    }

    public function addNote(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }
}
