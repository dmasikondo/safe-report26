<?php

namespace Tests\Feature\Staff;

use App\Enums\ReportStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_every_account_gets_a_random_uuid_used_in_its_profile_url(): void
    {
        [$first, $second] = User::factory()->count(2)->create();

        $this->assertTrue(Str::isUuid($first->uuid));
        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertSame(url("/staff/users/{$first->uuid}"), route('staff.users.show', $first));
    }

    public function test_profiles_cannot_be_opened_by_numeric_id_or_a_malformed_identifier(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());

        $this->get("/staff/users/{$investigator->id}")->assertNotFound();
        $this->get('/staff/users/not-a-uuid')->assertNotFound();
        $this->get('/staff/users/'.Str::uuid())->assertNotFound();
        $this->get(route('staff.users.show', $investigator))->assertOk()->assertSee($investigator->full_name);
    }

    public function test_admin_can_open_any_profile(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());

        foreach ([RoleEnum::SUPERVISOR, RoleEnum::INVESTIGATOR] as $role) {
            $this->get(route('staff.users.show', User::factory()->withRole($role)->create()))->assertOk();
        }
    }

    public function test_supervisor_can_open_their_own_team_members_but_no_one_else(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $teamMember = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['supervisor_id' => $supervisor->id]);
        $otherInvestigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $otherSupervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();

        $this->actingAs($supervisor);

        $this->get(route('staff.users.show', $supervisor))->assertOk();
        $this->get(route('staff.users.show', $teamMember))->assertOk();
        $this->get(route('staff.users.show', $otherInvestigator))->assertForbidden();
        $this->get(route('staff.users.show', $otherSupervisor))->assertForbidden();
    }

    public function test_investigator_can_only_open_their_own_profile(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['supervisor_id' => $supervisor->id]);

        $this->actingAs($investigator);

        $this->get(route('staff.users.show', $investigator))->assertOk();
        $this->get(route('staff.users.show', $supervisor))->assertForbidden();
        $this->get(route('staff.users.show', User::factory()->withRole(RoleEnum::INVESTIGATOR)->create()))->assertForbidden();
    }

    public function test_supervisor_profile_lists_the_team_with_open_case_counts(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create([
            'first_name' => 'Rudo',
            'last_name' => 'Chikore',
            'supervisor_id' => $supervisor->id,
        ]);
        Report::factory()->count(2)->assignedTo($investigator)->create();
        Report::factory()->assignedTo($investigator)->withStatus(ReportStatusEnum::CLOSED)->create();

        $this->get(route('staff.users.show', $supervisor))
            ->assertOk()
            ->assertSee('Rudo Chikore')
            ->assertSee(route('staff.users.show', $investigator))
            ->assertSee('2 open cases');
    }

    public function test_investigator_profile_links_to_their_supervisor(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create(['first_name' => 'Chipo', 'last_name' => 'Mutasa']);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['supervisor_id' => $supervisor->id]);

        $this->get(route('staff.users.show', $investigator))
            ->assertSee('Chipo Mutasa')
            ->assertSee(route('staff.users.show', $supervisor));
    }

    public function test_assigned_cases_show_open_cases_by_default_without_report_content(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $openCase = Report::factory()->assignedTo($investigator)->highPriority()->create();
        $closedCase = Report::factory()->assignedTo($investigator)->withStatus(ReportStatusEnum::CLOSED)->create();
        $someoneElsesCase = Report::factory()->create();

        $component = Livewire::test('pages::staff.users.show', ['user' => $investigator])
            ->assertSee($openCase->reference)
            ->assertSee(route('staff.reports.show', $openCase))
            ->assertDontSee($closedCase->reference)
            ->assertDontSee($someoneElsesCase->reference)
            ->assertDontSee($openCase->subject)
            ->assertDontSee($openCase->tracking_token)
            ->assertSee('1 open')
            ->assertSee('1 high priority')
            ->assertSee('1 resolved or closed');

        $component->set('cases', 'closed')
            ->assertSee($closedCase->reference)
            ->assertDontSee($openCase->reference);

        $component->set('cases', 'all')
            ->assertSee($closedCase->reference)
            ->assertSee($openCase->reference);
    }

    public function test_investigator_sees_their_own_cases_on_their_profile(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $myCase = Report::factory()->assignedTo($investigator)->create();

        $this->actingAs($investigator)
            ->get(route('staff.users.show', $investigator))
            ->assertSee($myCase->reference);
    }

    public function test_admin_deactivates_an_account_from_the_profile(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();

        Livewire::test('pages::staff.users.show', ['user' => $investigator])
            ->call('toggleActive', $investigator->uuid)
            ->assertHasNoErrors()
            ->assertSee('Deactivated');

        $this->assertFalse($investigator->fresh()->is_active);
    }

    public function test_account_actions_reject_numeric_ids_and_malformed_identifiers(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();

        Livewire::test('pages::staff.users.index')
            ->call('toggleActive', (string) $investigator->id)
            ->assertNotFound();

        Livewire::test('pages::staff.users.index')
            ->call('toggleActive', 'not-a-uuid')
            ->assertNotFound();

        $this->assertTrue($investigator->fresh()->is_active);
    }

    public function test_names_link_to_profiles_only_where_the_viewer_may_open_them(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $teamMember = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create(['supervisor_id' => $supervisor->id]);
        $outsider = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();

        $this->actingAs($supervisor);

        $this->blade('<x-staff.user-link :user="$user" />', ['user' => $teamMember])
            ->assertSee(route('staff.users.show', $teamMember));

        $this->blade('<x-staff.user-link :user="$user" />', ['user' => $outsider])
            ->assertSee($outsider->full_name)
            ->assertDontSee(route('staff.users.show', $outsider));

        $this->blade('<x-staff.user-link :user="null" fallback="Unassigned" />')
            ->assertSee('Unassigned');
    }
}
