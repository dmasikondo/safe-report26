<?php

namespace Tests\Feature\Policies;

use App\Enums\ReportStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, array{RoleEnum}>
     */
    public static function supervisoryRoles(): array
    {
        return [
            'superadmin' => [RoleEnum::SUPERADMIN],
            'admin' => [RoleEnum::ADMIN],
            'supervisor' => [RoleEnum::SUPERVISOR],
        ];
    }

    #[DataProvider('supervisoryRoles')]
    public function test_supervisory_roles_can_view_and_manage_any_case(RoleEnum $role): void
    {
        $user = User::factory()->withRole($role)->create();
        $report = Report::factory()->create();

        $this->assertTrue($user->can('viewAny', Report::class));
        $this->assertTrue($user->can('view', $report));
        $this->assertTrue($user->can('assign', $report));
        $this->assertTrue($user->can('changePriority', $report));
        $this->assertTrue($user->can('addNote', $report));
        $this->assertTrue($user->can('changeStatus', [$report, ReportStatusEnum::CLOSED]));
    }

    public function test_investigator_can_view_and_work_only_their_assigned_case(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $assigned = Report::factory()->assignedTo($investigator)->create();
        $unassigned = Report::factory()->create();

        $this->assertTrue($investigator->can('viewAny', Report::class));
        $this->assertTrue($investigator->can('view', $assigned));
        $this->assertTrue($investigator->can('addNote', $assigned));
        $this->assertTrue($investigator->can('changeStatus', [$assigned, ReportStatusEnum::RECEIVED]));

        $this->assertFalse($investigator->can('view', $unassigned));
        $this->assertFalse($investigator->can('addNote', $unassigned));
        $this->assertFalse($investigator->can('changeStatus', [$unassigned, ReportStatusEnum::RECEIVED]));
    }

    public function test_investigator_cannot_assign_or_reprioritise_their_own_case(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();

        $this->assertFalse($investigator->can('assign', $report));
        $this->assertFalse($investigator->can('changePriority', $report));
    }

    public function test_investigator_cannot_close_or_reopen_their_case(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $open = Report::factory()->assignedTo($investigator)->withStatus(ReportStatusEnum::INVESTIGATING)->create();
        $resolved = Report::factory()->assignedTo($investigator)->withStatus(ReportStatusEnum::RESOLVED)->create();

        $this->assertTrue($investigator->can('changeStatus', [$open, ReportStatusEnum::RESOLVED]));
        $this->assertFalse($investigator->can('changeStatus', [$open, ReportStatusEnum::CLOSED]));
        $this->assertFalse($investigator->can('changeStatus', [$resolved, ReportStatusEnum::INVESTIGATING]));
    }

    public function test_nobody_may_skip_workflow_steps(): void
    {
        $superadmin = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();
        $report = Report::factory()->withStatus(ReportStatusEnum::SUBMITTED)->create();

        $this->assertFalse($superadmin->can('changeStatus', [$report, ReportStatusEnum::RESOLVED]));
    }

    public function test_user_without_a_role_cannot_see_cases(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', Report::class));
        $this->assertFalse($user->can('view', Report::factory()->create()));
    }
}
