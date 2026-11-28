<?php

namespace Tests\Feature\Settings;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_name_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertEquals('Test User', $user->refresh()->full_name);
    }

    public function test_users_cannot_change_their_own_email_address(): void
    {
        $user = User::factory()->create(['email' => 'original@example.org']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('email', 'attacker@example.org')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('original@example.org', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_superadmin_can_change_their_own_email_address(): void
    {
        $this->seed(RoleSeeder::class);
        $superadmin = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();

        $this->actingAs($superadmin)
            ->get(route('profile.edit'))
            ->assertDontSee('can change your email address');

        Livewire::test('pages::settings.profile')
            ->set('email', 'new.superadmin@example.org')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('new.superadmin@example.org', $superadmin->refresh()->email);
    }

    public function test_superadmin_email_must_be_unique(): void
    {
        $this->seed(RoleSeeder::class);
        $other = User::factory()->create();

        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERADMIN)->create());

        Livewire::test('pages::settings.profile')
            ->set('email', $other->email)
            ->call('updateProfileInformation')
            ->assertHasErrors('email');
    }

    public function test_email_notice_is_shown_only_to_non_admins(): void
    {
        $this->seed(RoleSeeder::class);
        $notice = 'Only an administrator can change your email address.';

        $this->actingAs(User::factory()->withRole(RoleEnum::INVESTIGATOR)->create())
            ->get(route('profile.edit'))
            ->assertSee($notice);

        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create(['email' => 'admin@example.org']);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertDontSee($notice);

        Livewire::test('pages::settings.profile')
            ->set('email', 'changed@example.org')
            ->call('updateProfileInformation');

        $this->assertSame('admin@example.org', $admin->refresh()->email);
    }

    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('delete-user-button');

        $this->assertNotNull($user->fresh());
    }
}
