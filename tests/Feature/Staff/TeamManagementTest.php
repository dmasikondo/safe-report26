<?php

namespace Tests\Feature\Staff;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\TeamBalance;
use Database\Seeders\DemoStaffSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function supervisor(array $attributes = []): User
    {
        return User::factory()->withRole(RoleEnum::SUPERVISOR)->create($attributes);
    }

    private function investigator(array $attributes = []): User
    {
        return User::factory()->withRole(RoleEnum::INVESTIGATOR)->create($attributes);
    }

    // ── Access ────────────────────────────────────────────────────────────

    public function test_admins_can_open_the_teams_page(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create())
            ->get(route('staff.teams.index'))
            ->assertOk();
    }

    public function test_supervisors_and_investigators_cannot_open_the_teams_page(): void
    {
        foreach ([RoleEnum::SUPERVISOR, RoleEnum::INVESTIGATOR] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())
                ->get(route('staff.teams.index'))
                ->assertForbidden();
        }
    }

    // ── Assigning ─────────────────────────────────────────────────────────

    public function test_admin_assigns_moves_and_removes_an_investigator(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $first = $this->supervisor();
        $second = $this->supervisor();
        $investigator = $this->investigator();

        $page = Livewire::test('pages::staff.teams.index')
            ->set("selectedSupervisor.{$investigator->id}", (string) $first->id)
            ->call('assign', $investigator->id)
            ->assertHasNoErrors();
        $this->assertSame($first->id, $investigator->fresh()->supervisor_id);

        $page->set("selectedSupervisor.{$investigator->id}", (string) $second->id)
            ->call('assign', $investigator->id)
            ->assertHasNoErrors();
        $this->assertSame($second->id, $investigator->fresh()->supervisor_id);

        $page->call('unassign', $investigator->id);
        $this->assertNull($investigator->fresh()->supervisor_id);
    }

    public function test_investigators_can_only_join_an_active_supervisor(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $inactiveSupervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->inactive()->create();
        $otherInvestigator = $this->investigator();
        $investigator = $this->investigator();

        foreach ([$inactiveSupervisor, $otherInvestigator] as $target) {
            Livewire::test('pages::staff.teams.index')
                ->set("selectedSupervisor.{$investigator->id}", (string) $target->id)
                ->call('assign', $investigator->id)
                ->assertHasErrors("selectedSupervisor.{$investigator->id}");
        }

        $this->assertNull($investigator->fresh()->supervisor_id);
    }

    public function test_only_investigators_can_be_placed_in_a_team(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $supervisor = $this->supervisor();
        $otherSupervisor = $this->supervisor();

        Livewire::test('pages::staff.teams.index')
            ->set("selectedSupervisor.{$otherSupervisor->id}", (string) $supervisor->id)
            ->call('assign', $otherSupervisor->id)
            ->assertNotFound();

        $this->assertNull($otherSupervisor->fresh()->supervisor_id);
    }

    // ── Balance ───────────────────────────────────────────────────────────

    public function test_uneven_teams_are_flagged(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $busy = $this->supervisor(['first_name' => 'Busy']);
        $this->supervisor(['first_name' => 'Idle']);
        User::factory()->withRole(RoleEnum::INVESTIGATOR)->count(4)->create(['supervisor_id' => $busy->id]);

        // 4 investigators, 2 supervisors: fair share 2, so 4 is +2 and 0 is −2.
        Livewire::test('pages::staff.teams.index')
            ->assertSee('Too many (+2)')
            ->assertSee('Too few (−2)');
    }

    public function test_fair_share_rule(): void
    {
        $balance = new TeamBalance(investigatorCount: 12, supervisorCount: 3);

        $this->assertSame(4.0, $balance->fairShare());
        $this->assertSame(TeamBalance::BALANCED, $balance->statusFor(4));
        $this->assertSame(TeamBalance::TOO_MANY, $balance->statusFor(6));
        $this->assertSame(2, $balance->differenceFor(6));
        $this->assertSame(TeamBalance::TOO_FEW, $balance->statusFor(1));
        $this->assertSame(-3, $balance->differenceFor(1));

        // 13 ÷ 3 = 4.33…: teams of 4 or 5 are both fair.
        $uneven = new TeamBalance(investigatorCount: 13, supervisorCount: 3);
        $this->assertSame(TeamBalance::BALANCED, $uneven->statusFor(5));
        $this->assertSame(TeamBalance::BALANCED, $uneven->statusFor(4));

        $this->assertSame(TeamBalance::BALANCED, (new TeamBalance(5, 0))->statusFor(5));
    }

    // ── Demo data ─────────────────────────────────────────────────────────

    public function test_demo_staff_seeder_creates_unassigned_supervisors_and_investigators(): void
    {
        $this->seed(DemoStaffSeeder::class);
        $this->seed(DemoStaffSeeder::class);

        $supervisors = User::withRole(RoleEnum::SUPERVISOR)->get();
        $investigators = User::withRole(RoleEnum::INVESTIGATOR)->get();

        $this->assertCount(2, $supervisors);
        $this->assertCount(12, $investigators);
        $this->assertTrue($investigators->every(fn (User $user): bool => $user->supervisor_id === null && $user->department_id !== null));
        $this->assertTrue(Hash::check('password', $supervisors->first()->password));
    }
}
