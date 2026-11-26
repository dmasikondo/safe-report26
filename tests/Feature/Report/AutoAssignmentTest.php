<?php

namespace Tests\Feature\Report;

use App\Enums\ReportActivityTypeEnum;
use App\Enums\ReportCategoryEnum;
use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\SensitiveReportReceivedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AutoAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function submitReport(array $overrides = []): Report
    {
        $this->post(route('report.store'), array_merge([
            'category' => ReportCategoryEnum::CORRUPTION->value,
            'subject' => 'Cash demanded for hostel room allocation',
            'body' => str_repeat('Full detail about the incident. ', 10),
        ], $overrides))->assertRedirect();

        return Report::latest('id')->firstOrFail();
    }

    private function investigator(?Department $department = null, ?User $supervisor = null): User
    {
        return User::factory()->withRole(RoleEnum::INVESTIGATOR)->create([
            'department_id' => ($department ?? Department::factory()->create())->id,
            'supervisor_id' => $supervisor?->id,
        ]);
    }

    public function test_report_goes_to_the_least_busy_investigator_outside_its_department(): void
    {
        $finance = Department::factory()->create();
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $insider = $this->investigator($finance);
        $busy = $this->investigator(supervisor: $supervisor);
        $free = $this->investigator(supervisor: $supervisor);
        Report::factory()->assignedTo($busy)->create();

        $report = $this->submitReport(['department_id' => $finance->id]);

        $this->assertSame($free->id, $report->assigned_to);
        $this->assertDatabaseHas('report_activities', [
            'report_id' => $report->id,
            'type' => ReportActivityTypeEnum::ASSIGNED->value,
            'user_id' => null,
            'is_automatic' => true,
        ]);

        Notification::assertSentTo(
            [$free, $supervisor],
            CaseAssignedNotification::class,
            fn (CaseAssignedNotification $notification, array $channels): bool => $notification->report->is($report)
                && $channels === ['database', 'mail'],
        );
        Notification::assertNotSentTo([$insider, $busy], CaseAssignedNotification::class);
    }

    public function test_sexual_harassment_reports_are_not_assigned_and_admins_are_notified(): void
    {
        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create();
        $superAdmin = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $investigator = $this->investigator();

        $report = $this->submitReport(['category' => ReportCategoryEnum::SEXUAL_HARASSMENT->value]);

        $this->assertNull($report->assigned_to);
        Notification::assertSentTo(
            [$admin, $superAdmin],
            SensitiveReportReceivedNotification::class,
            fn (SensitiveReportReceivedNotification $notification): bool => $notification->reason === SensitiveReportReceivedNotification::SEXUAL_HARASSMENT,
        );
        Notification::assertNotSentTo([$supervisor, $investigator], SensitiveReportReceivedNotification::class);
        Notification::assertNothingSentTo($investigator);
    }

    public function test_reports_stay_unassigned_when_automatic_assignment_is_off(): void
    {
        Setting::put(Setting::AUTO_ASSIGN_CASES, false);
        $this->investigator();

        $report = $this->submitReport();

        $this->assertNull($report->assigned_to);
        Notification::assertNotSentTo(User::all(), CaseAssignedNotification::class);
    }

    public function test_admins_are_told_when_no_investigator_is_eligible(): void
    {
        $finance = Department::factory()->create();
        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create();
        $this->investigator($finance);

        $report = $this->submitReport(['department_id' => $finance->id]);

        $this->assertNull($report->assigned_to);
        Notification::assertSentTo(
            $admin,
            SensitiveReportReceivedNotification::class,
            fn (SensitiveReportReceivedNotification $notification): bool => $notification->reason === SensitiveReportReceivedNotification::NO_ELIGIBLE_INVESTIGATOR,
        );
    }
}
