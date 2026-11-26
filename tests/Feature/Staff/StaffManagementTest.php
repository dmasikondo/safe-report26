<?php

namespace Tests\Feature\Staff;

use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /**
     * @return array{first_name: string, last_name: string, email: string}
     */
    private function newStaffDetails(): array
    {
        return [
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'email' => 'tendai.moyo@example.org',
        ];
    }

    private function submitCreateForm(array $details, RoleEnum $role, ?Department $department = null): Testable
    {
        return Livewire::test('pages::staff.users.create')
            ->set('first_name', $details['first_name'])
            ->set('last_name', $details['last_name'])
            ->set('email', $details['email'])
            ->set('role', $role->value)
            ->set('department_id', $department ? (string) $department->id : '')
            ->call('save');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_superadmin_creates_an_admin_and_invitation_is_sent(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::ADMIN)
            ->assertHasNoErrors()
            ->assertRedirect(route('staff.users.index'));

        $created = User::where('email', 'tendai.moyo@example.org')->firstOrFail();

        $this->assertTrue($created->hasRole(RoleEnum::ADMIN));
        $this->assertTrue($created->is_active);
        Notification::assertSentTo($created, StaffInvitationNotification::class);
    }

    public function test_admin_creates_supervisors_and_investigators(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        [$finance, $procurement] = Department::factory()->count(2)->create();

        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::SUPERVISOR, $finance)->assertHasNoErrors();
        $this->submitCreateForm([...$this->newStaffDetails(), 'email' => 'rudo@example.org'], RoleEnum::INVESTIGATOR, $procurement)->assertHasNoErrors();

        $supervisor = User::where('email', 'tendai.moyo@example.org')->firstOrFail();
        $investigator = User::where('email', 'rudo@example.org')->firstOrFail();

        $this->assertTrue($supervisor->hasRole(RoleEnum::SUPERVISOR));
        $this->assertTrue($supervisor->department->is($finance));
        $this->assertTrue($investigator->hasRole(RoleEnum::INVESTIGATOR));
        $this->assertTrue($investigator->department->is($procurement));
    }

    public function test_supervisors_and_investigators_must_have_a_department(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());

        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::SUPERVISOR)
            ->assertHasErrors(['department_id' => 'required']);
        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::INVESTIGATOR)
            ->assertHasErrors(['department_id' => 'required']);

        $this->assertDatabaseMissing('users', ['email' => 'tendai.moyo@example.org']);
    }

    public function test_new_investigator_can_be_placed_in_a_supervisors_team(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create(['first_name' => 'Chipo', 'last_name' => 'Mutasa']);

        Livewire::test('pages::staff.users.create')
            ->set('first_name', 'Tendai')
            ->set('last_name', 'Moyo')
            ->set('email', 'tendai.moyo@example.org')
            ->set('role', RoleEnum::INVESTIGATOR->value)
            ->set('department_id', (string) Department::factory()->create()->id)
            ->set('supervisor_id', (string) $supervisor->id)
            ->call('save')
            ->assertHasNoErrors();

        $investigator = User::where('email', 'tendai.moyo@example.org')->firstOrFail();
        $this->assertTrue($investigator->supervisor->is($supervisor));

        $this->get(route('staff.users.index'))->assertSee('Chipo Mutasa');
    }

    public function test_supervisor_choice_must_be_an_active_supervisor_and_only_applies_to_investigators(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $inactiveSupervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->inactive()->create();
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $department = Department::factory()->create();

        Livewire::test('pages::staff.users.create')
            ->set('first_name', 'Tendai')
            ->set('last_name', 'Moyo')
            ->set('email', 'tendai.moyo@example.org')
            ->set('role', RoleEnum::INVESTIGATOR->value)
            ->set('department_id', (string) $department->id)
            ->set('supervisor_id', (string) $inactiveSupervisor->id)
            ->call('save')
            ->assertHasErrors(['supervisor_id' => 'Choose an active supervisor.'])
            ->set('role', RoleEnum::SUPERVISOR->value)
            ->set('supervisor_id', (string) $supervisor->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(User::where('email', 'tendai.moyo@example.org')->firstOrFail()->supervisor_id);
    }

    public function test_administrators_are_created_without_a_department(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::ADMIN, Department::factory()->create())
            ->assertHasNoErrors();

        $this->assertNull(User::where('email', 'tendai.moyo@example.org')->firstOrFail()->department_id);
    }

    public function test_admin_cannot_create_an_admin(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());

        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::ADMIN)
            ->assertHasErrors(['role' => 'You are not allowed to create accounts with this role.']);

        $this->assertDatabaseMissing('users', ['email' => 'tendai.moyo@example.org']);
        Notification::assertNothingSent();
    }

    public function test_supervisor_cannot_open_the_create_staff_page(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERVISOR)->create())
            ->get(route('staff.users.create'))
            ->assertForbidden();
    }

    public function test_investigator_cannot_open_the_staff_list(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::INVESTIGATOR)->create())
            ->get(route('staff.users.index'))
            ->assertForbidden();
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        $this->submitCreateForm(['first_name' => '', 'last_name' => '', 'email' => ''], RoleEnum::INVESTIGATOR)
            ->assertHasErrors(['first_name' => 'required', 'last_name' => 'required', 'email' => 'required']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'tendai.moyo@example.org']);
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::INVESTIGATOR)
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_admin_deactivates_an_investigator(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();

        Livewire::test('pages::staff.users.index')
            ->call('toggleActive', $investigator->id)
            ->assertHasNoErrors();

        $this->assertFalse($investigator->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_another_admin(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $otherAdmin = User::factory()->withRole(RoleEnum::ADMIN)->create();

        Livewire::test('pages::staff.users.index')
            ->call('toggleActive', $otherAdmin->id)
            ->assertForbidden();

        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_admin_resends_an_investigator_invitation(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();

        Livewire::test('pages::staff.users.index')
            ->call('resendInvitation', $investigator->id)
            ->assertHasNoErrors();

        Notification::assertSentTo($investigator, StaffInvitationNotification::class);
    }

    public function test_invitation_link_lets_the_new_staff_member_set_a_password(): void
    {
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());
        $this->submitCreateForm($this->newStaffDetails(), RoleEnum::INVESTIGATOR, Department::factory()->create());
        auth()->logout();

        $created = User::where('email', 'tendai.moyo@example.org')->firstOrFail();

        Notification::assertSentTo($created, StaffInvitationNotification::class, function (StaffInvitationNotification $notification) use ($created) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $created->email,
                'password' => 'a-new-password',
                'password_confirmation' => 'a-new-password',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->assertTrue(auth()->attempt(['email' => $created->email, 'password' => 'a-new-password']));
    }
}
