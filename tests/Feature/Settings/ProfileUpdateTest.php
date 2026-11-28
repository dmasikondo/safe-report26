<?php

namespace Tests\Feature\Settings;

use App\Models\User;
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
