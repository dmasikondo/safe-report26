<?php

namespace Tests\Feature\Staff;

use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\Report;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\StaffEmailChangedNotification;
use App\Notifications\StaffInvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class StaffEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function admin(): User
    {
        return User::factory()->withRole(RoleEnum::ADMIN)->create();
    }

    private function investigator(?Department $department = null): User
    {
        return User::factory()->withRole(RoleEnum::INVESTIGATOR)->create([
            'department_id' => ($department ?? Department::factory()->create())->id,
        ]);
    }

    // ── Access ────────────────────────────────────────────────────────────

    public function test_admin_edits_an_investigators_name_department_and_supervisor(): void
    {
        $admin = $this->admin();
        $investigator = $this->investigator();
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $finance = Department::factory()->create(['name' => 'Finance']);

        $this->actingAs($admin);

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('first_name', 'Rudo')
            ->set('department_id', (string) $finance->id)
            ->set('supervisor_id', (string) $supervisor->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('staff.users.show', $investigator));

        $investigator->refresh();
        $this->assertSame('Rudo', $investigator->first_name);
        $this->assertTrue($investigator->department->is($finance));
        $this->assertTrue($investigator->supervisor->is($supervisor));
    }

    public function test_admin_cannot_edit_another_admin_or_a_superadmin(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('staff.users.edit', $this->admin()))->assertForbidden();
        $this->get(route('staff.users.edit', User::factory()->withRole(RoleEnum::SUPERADMIN)->create()))->assertForbidden();
    }

    public function test_supervisors_and_investigators_cannot_edit_accounts(): void
    {
        $target = $this->investigator();

        foreach ([RoleEnum::SUPERVISOR, RoleEnum::INVESTIGATOR] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())
                ->get(route('staff.users.edit', $target))
                ->assertForbidden();
        }
    }

    public function test_nobody_edits_their_own_account_from_the_staff_area(): void
    {
        $superadmin = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();

        $this->actingAs($superadmin)
            ->get(route('staff.users.edit', $superadmin))
            ->assertForbidden();
    }

    public function test_edit_button_is_shown_only_for_editable_accounts(): void
    {
        $investigator = $this->investigator();
        $otherAdmin = $this->admin();

        $this->actingAs($this->admin())
            ->get(route('staff.users.index'))
            ->assertOk()
            ->assertSee(route('staff.users.edit', $investigator))
            ->assertDontSee(route('staff.users.edit', $otherAdmin));
    }

    // ── Roles ─────────────────────────────────────────────────────────────

    public function test_admin_switches_an_investigator_to_supervisor(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $investigator = $this->investigator();
        $investigator->update(['supervisor_id' => $supervisor->id]);

        $this->actingAs($this->admin());

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('role', RoleEnum::SUPERVISOR->value)
            ->call('save')
            ->assertHasNoErrors();

        $investigator->refresh();
        $this->assertSame(RoleEnum::SUPERVISOR, $investigator->primaryRole());
        $this->assertNull($investigator->supervisor_id);
    }

    public function test_admin_cannot_give_the_admin_role(): void
    {
        $investigator = $this->investigator();

        $this->actingAs($this->admin());

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('role', RoleEnum::ADMIN->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(RoleEnum::INVESTIGATOR, $investigator->fresh()->primaryRole());
    }

    public function test_superadmin_can_promote_a_supervisor_to_admin(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create(['department_id' => Department::factory()->create()->id]);

        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        Livewire::test('pages::staff.users.edit', ['user' => $supervisor])
            ->set('role', RoleEnum::ADMIN->value)
            ->call('save')
            ->assertHasNoErrors();

        $supervisor->refresh();
        $this->assertSame(RoleEnum::ADMIN, $supervisor->primaryRole());
        $this->assertNull($supervisor->department_id);
    }

    public function test_the_last_active_superadmin_cannot_be_demoted(): void
    {
        $onlySuperadmin = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();
        $editor = User::factory()->withRole(RoleEnum::SUPERADMIN)->inactive()->create();

        $this->assertFalse($editor->can('changeRole', [$onlySuperadmin, RoleEnum::ADMIN]));

        $activeEditor = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();
        $this->assertTrue($activeEditor->can('changeRole', [$onlySuperadmin, RoleEnum::ADMIN]));
    }

    public function test_supervisor_with_a_team_cannot_become_an_investigator(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create(['department_id' => Department::factory()->create()->id]);
        $this->investigator()->update(['supervisor_id' => $supervisor->id]);

        $this->actingAs($this->admin());

        Livewire::test('pages::staff.users.edit', ['user' => $supervisor])
            ->set('role', RoleEnum::INVESTIGATOR->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(RoleEnum::SUPERVISOR, $supervisor->fresh()->primaryRole());
    }

    public function test_staff_with_open_cases_cannot_move_to_a_role_that_cannot_hold_cases(): void
    {
        $investigator = $this->investigator();
        Report::factory()->assignedTo($investigator)->create();

        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('role', RoleEnum::ADMIN->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(RoleEnum::INVESTIGATOR, $investigator->fresh()->primaryRole());
    }

    // ── Email changes ─────────────────────────────────────────────────────

    public function test_changing_the_email_reinvites_and_signs_the_person_out(): void
    {
        config(['session.driver' => 'database']);

        $investigator = $this->investigator();
        $investigator->update(['password' => 'old-password']);
        DB::table('sessions')->insert([
            'id' => 'session-1',
            'user_id' => $investigator->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin());

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('email', 'new.address@example.org')
            ->call('save')
            ->assertHasNoErrors();

        $investigator->refresh();
        $this->assertSame('new.address@example.org', $investigator->email);
        $this->assertFalse(Hash::check('old-password', $investigator->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $investigator->id]);

        Notification::assertSentTo($investigator, StaffInvitationNotification::class, fn ($notification) => $notification->afterEmailChange);
        Notification::assertSentTo(
            new AnonymousNotifiable,
            StaffEmailChangedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] !== 'new.address@example.org',
        );
    }

    public function test_keeping_the_same_email_sends_nothing(): void
    {
        $investigator = $this->investigator();

        $this->actingAs($this->admin());

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('last_name', 'Chikwanha')
            ->call('save')
            ->assertHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_email_must_stay_unique(): void
    {
        $investigator = $this->investigator();
        $other = $this->investigator();

        $this->actingAs($this->admin());

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('email', $other->email)
            ->call('save')
            ->assertHasErrors('email');
    }

    // ── Department conflicts ──────────────────────────────────────────────

    public function test_moving_into_the_department_of_an_open_case_needs_confirmation(): void
    {
        $exams = Department::factory()->create(['name' => 'Exams']);
        $investigator = $this->investigator();
        $case = Report::factory()->assignedTo($investigator)->forDepartment($exams)->create();

        $this->actingAs($this->admin());

        $page = Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('department_id', (string) $exams->id)
            ->call('save')
            ->assertHasErrors('department_id')
            ->assertSee($case->reference);

        $this->assertNotSame($exams->id, $investigator->fresh()->department_id);

        $page->call('save')->assertHasNoErrors();

        $this->assertSame($exams->id, $investigator->fresh()->department_id);
    }

    // ── Account history ───────────────────────────────────────────────────

    public function test_each_changed_field_is_recorded_with_who_changed_it(): void
    {
        $admin = $this->admin();
        $investigator = $this->investigator();
        $investigator->update(['first_name' => 'Tendai']);
        $finance = Department::factory()->create(['name' => 'Finance']);

        $this->actingAs($admin);

        Livewire::test('pages::staff.users.edit', ['user' => $investigator])
            ->set('first_name', 'Rudo')
            ->set('department_id', (string) $finance->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, StaffActivity::count());
        $this->assertDatabaseHas('staff_activities', [
            'user_id' => $investigator->id,
            'actor_id' => $admin->id,
            'field' => 'first_name',
            'from_value' => 'Tendai',
            'to_value' => 'Rudo',
        ]);
        $this->assertDatabaseHas('staff_activities', ['field' => 'department', 'to_value' => 'Finance']);

        $this->get(route('staff.users.show', $investigator))
            ->assertOk()
            ->assertSee('Account history')
            ->assertSeeInOrder(['First name', 'Tendai', 'Rudo']);
    }

    public function test_account_history_is_hidden_from_supervisors(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $investigator = $this->investigator();
        $investigator->update(['supervisor_id' => $supervisor->id]);

        $this->actingAs($supervisor)
            ->get(route('staff.users.show', $investigator))
            ->assertOk()
            ->assertDontSee('Account history');
    }
}
