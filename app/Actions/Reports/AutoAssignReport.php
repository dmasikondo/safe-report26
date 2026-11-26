<?php

namespace App\Actions\Reports;

use App\Enums\ReportCategoryEnum;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SensitiveReportReceivedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Handles a newly submitted report.
 *
 * Sexual harassment reports are never assigned automatically; administrators
 * are told so they can choose who handles them. When automatic assignment is
 * switched on, every other report goes to the active investigator outside the
 * report's department who has the fewest open cases.
 */
class AutoAssignReport
{
    public const REASON = 'Assigned automatically to the investigator with the fewest open cases.';

    public function __construct(public NotifyCaseAssignment $notifyCaseAssignment) {}

    public function handle(Report $report): ?User
    {
        if ($report->category === ReportCategoryEnum::SEXUAL_HARASSMENT) {
            $this->notifyAdmins($report, SensitiveReportReceivedNotification::SEXUAL_HARASSMENT);

            return null;
        }

        if (! Setting::autoAssignEnabled()) {
            return null;
        }

        $investigator = $this->leastBusyInvestigator($report);

        if ($investigator === null) {
            $this->notifyAdmins($report, SensitiveReportReceivedNotification::NO_ELIGIBLE_INVESTIGATOR);

            return null;
        }

        $report->assignTo($investigator, actor: null, reason: self::REASON);
        $this->notifyCaseAssignment->handle($report, $investigator, actor: null);

        return $investigator;
    }

    private function leastBusyInvestigator(Report $report): ?User
    {
        return User::query()
            ->active()
            ->withRole(RoleEnum::INVESTIGATOR)
            ->outsideDepartment($report->department_id)
            ->withCount(['assignedReports as open_cases_count' => fn ($query) => $query->open()])
            ->orderBy('open_cases_count')
            ->orderBy('id')
            ->first();
    }

    private function notifyAdmins(Report $report, string $reason): void
    {
        $admins = User::query()
            ->active()
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', [RoleEnum::SUPERADMIN->value, RoleEnum::ADMIN->value]))
            ->get();

        Notification::send($admins, new SensitiveReportReceivedNotification($report, $reason));
    }
}
