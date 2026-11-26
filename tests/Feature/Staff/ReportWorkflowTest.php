<?php

namespace Tests\Feature\Staff;

use App\Enums\ReportActivityTypeEnum;
use App\Enums\ReportStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    // ── Access ────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('staff.reports.index'))->assertRedirect(route('login'));
    }

    public function test_deactivated_staff_are_signed_out(): void
    {
        $user = User::factory()->withRole(RoleEnum::SUPERVISOR)->inactive()->create();

        $this->actingAs($user)
            ->get(route('staff.reports.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_investigator_cannot_open_a_case_assigned_to_someone_else(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->create();

        $this->actingAs($investigator)
            ->get(route('staff.reports.show', $report))
            ->assertForbidden();
    }

    public function test_investigator_list_shows_only_their_cases(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $mine = Report::factory()->assignedTo($investigator)->create(['subject' => 'Ghost workers on payroll']);
        Report::factory()->create(['subject' => 'Inflated tender at council']);

        $this->actingAs($investigator)
            ->get(route('staff.reports.index'))
            ->assertOk()
            ->assertSee($mine->subject)
            ->assertDontSee('Inflated tender at council');
    }

    public function test_supervisor_list_shows_every_open_case(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $reports = Report::factory()->count(2)->create();

        $response = $this->actingAs($supervisor)->get(route('staff.reports.index'))->assertOk();

        foreach ($reports as $report) {
            $response->assertSee($report->subject);
        }
    }

    public function test_list_can_be_filtered_by_department(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $exams = Department::factory()->create(['name' => 'Exams']);
        Report::factory()->forDepartment($exams)->create(['subject' => 'Leaked exam papers']);
        Report::factory()->forDepartment(Department::factory()->create())->create(['subject' => 'Missing canteen stock']);

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.index')
            ->set('department', (string) $exams->id)
            ->assertSee('Leaked exam papers')
            ->assertDontSee('Missing canteen stock');
    }

    public function test_list_can_be_searched_by_tracking_token(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $wanted = Report::factory()->create(['subject' => 'Fuel taken from workshop']);
        Report::factory()->create(['subject' => 'Hostel rooms sold']);

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.index')
            ->set('search', strtolower($wanted->tracking_token))
            ->assertSee('Fuel taken from workshop')
            ->assertDontSee('Hostel rooms sold');
    }

    // ── Assignment ────────────────────────────────────────────────────────

    public function test_supervisor_assigns_a_case_to_an_investigator(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('assigneeId', (string) $investigator->id)
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertSame($investigator->id, $report->fresh()->assigned_to);
        $this->assertDatabaseHas('report_activities', [
            'report_id' => $report->id,
            'user_id' => $supervisor->id,
            'type' => ReportActivityTypeEnum::ASSIGNED->value,
            'to_value' => (string) $investigator->id,
        ]);
    }

    public function test_cases_cannot_be_assigned_to_inactive_or_non_case_staff(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $inactive = User::factory()->withRole(RoleEnum::INVESTIGATOR)->inactive()->create();
        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create();
        $report = Report::factory()->create();

        $this->actingAs($supervisor);

        foreach ([$inactive, $admin] as $candidate) {
            Livewire::test('pages::staff.reports.show', ['report' => $report])
                ->set('assigneeId', (string) $candidate->id)
                ->call('assign')
                ->assertHasErrors(['assigneeId' => 'Choose an active investigator or supervisor.']);
        }

        $this->assertNull($report->fresh()->assigned_to);
    }

    public function test_staff_from_the_case_department_are_hidden_and_cannot_be_assigned(): void
    {
        $finance = Department::factory()->create();
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $insider = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['first_name' => 'Insider', 'department_id' => $finance->id]);
        $outsider = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['first_name' => 'Outsider', 'department_id' => Department::factory()->create()->id]);
        $report = Report::factory()->forDepartment($finance)->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->assertSee('Outsider')
            ->assertDontSee('Insider')
            ->set('assigneeId', (string) $insider->id)
            ->call('assign')
            ->assertHasErrors('assigneeId')
            ->set('assigneeId', (string) $outsider->id)
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertSame($outsider->id, $report->fresh()->assigned_to);
    }

    public function test_reports_without_a_department_list_all_case_staff(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['first_name' => 'Insider', 'department_id' => Department::factory()->create()->id]);
        $report = Report::factory()->create(['department_id' => null]);

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->assertSee('Insider')
            ->assertDontSee('Show staff from');
    }

    public function test_same_department_staff_can_be_assigned_with_a_recorded_reason(): void
    {
        $finance = Department::factory()->create();
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $insider = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['department_id' => $finance->id]);
        $report = Report::factory()->forDepartment($finance)->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('includeSameDepartment', true)
            ->set('assigneeId', (string) $insider->id)
            ->call('assign')
            ->assertHasErrors(['assignmentReason' => 'required'])
            ->set('assignmentReason', 'Only investigator available in this region.')
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertSame($insider->id, $report->fresh()->assigned_to);
        $this->assertSame(
            'Only investigator available in this region.',
            $report->activities()->where('type', ReportActivityTypeEnum::ASSIGNED)->sole()->body,
        );
    }

    public function test_investigator_cannot_reassign_their_case(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $colleague = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();

        $this->actingAs($investigator);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('assigneeId', (string) $colleague->id)
            ->call('assign')
            ->assertForbidden();

        $this->assertSame($investigator->id, $report->fresh()->assigned_to);
    }

    // ── Status ────────────────────────────────────────────────────────────

    public function test_assigned_investigator_moves_case_forward_with_a_reason(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->withStatus(ReportStatusEnum::RECEIVED)->create();

        $this->actingAs($investigator);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('statusReason', 'Initial documents verified.')
            ->call('changeStatus', ReportStatusEnum::UNDER_REVIEW->value)
            ->assertHasNoErrors();

        $this->assertSame(ReportStatusEnum::UNDER_REVIEW, $report->fresh()->status);
        $this->assertDatabaseHas('report_activities', [
            'report_id' => $report->id,
            'type' => ReportActivityTypeEnum::STATUS_CHANGED->value,
            'from_value' => ReportStatusEnum::RECEIVED->value,
            'to_value' => ReportStatusEnum::UNDER_REVIEW->value,
        ]);
        $this->assertSame('Initial documents verified.', $report->activities()->first()->body);
    }

    public function test_investigator_cannot_close_a_case(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();

        $this->actingAs($investigator);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->call('changeStatus', ReportStatusEnum::CLOSED->value)
            ->assertForbidden();

        $this->assertSame(ReportStatusEnum::SUBMITTED, $report->fresh()->status);
    }

    public function test_supervisor_closes_a_case(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $report = Report::factory()->withStatus(ReportStatusEnum::UNDER_REVIEW)->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->call('changeStatus', ReportStatusEnum::CLOSED->value)
            ->assertHasNoErrors();

        $this->assertSame(ReportStatusEnum::CLOSED, $report->fresh()->status);
    }

    public function test_status_change_that_skips_steps_is_refused(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $report = Report::factory()->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->call('changeStatus', ReportStatusEnum::RESOLVED->value)
            ->assertForbidden();

        $this->assertSame(ReportStatusEnum::SUBMITTED, $report->fresh()->status);
    }

    // ── Priority & notes ──────────────────────────────────────────────────

    public function test_supervisor_changes_priority(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $report = Report::factory()->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('priority', '3')
            ->call('updatePriority')
            ->assertHasNoErrors();

        $this->assertSame(3, $report->fresh()->priority);
        $this->assertDatabaseHas('report_activities', [
            'report_id' => $report->id,
            'type' => ReportActivityTypeEnum::PRIORITY_CHANGED->value,
            'from_value' => '2',
            'to_value' => '3',
        ]);
    }

    public function test_assigned_investigator_adds_a_note_shown_in_the_case_log(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();

        $this->actingAs($investigator);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('note', 'Interviewed the procurement officer.')
            ->call('addNote')
            ->assertHasNoErrors()
            ->assertSet('note', '')
            ->assertSee('Interviewed the procurement officer.');

        $this->assertDatabaseHas('report_activities', [
            'report_id' => $report->id,
            'user_id' => $investigator->id,
            'type' => ReportActivityTypeEnum::NOTE_ADDED->value,
        ]);
    }

    public function test_empty_note_is_rejected(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $report = Report::factory()->create();

        $this->actingAs($supervisor);

        Livewire::test('pages::staff.reports.show', ['report' => $report])
            ->set('note', '')
            ->call('addNote')
            ->assertHasErrors(['note' => 'required']);

        $this->assertSame(0, $report->activities()->count());
    }
}
