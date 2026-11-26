<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_admin_switches_automatic_assignment_off_and_on(): void
    {
        $this->seed(RoleSeeder::class);
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());

        Livewire::test('pages::dashboard')
            ->assertSee('Automatic case assignment')
            ->set('autoAssign', false);
        $this->assertFalse(Setting::autoAssignEnabled());

        Livewire::test('pages::dashboard')
            ->assertSet('autoAssign', false)
            ->set('autoAssign', true);
        $this->assertTrue(Setting::autoAssignEnabled());
    }

    public function test_supervisors_cannot_change_automatic_assignment(): void
    {
        $this->seed(RoleSeeder::class);
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERVISOR)->create());

        Livewire::test('pages::dashboard')
            ->assertDontSee('Automatic case assignment')
            ->set('autoAssign', false)
            ->assertForbidden();

        $this->assertTrue(Setting::autoAssignEnabled());
    }

    public function test_unread_notifications_are_listed_and_can_be_marked_read(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $investigator->notify(new CaseAssignedNotification($report, $investigator));

        $this->actingAs($investigator);

        Livewire::test('pages::dashboard')
            ->assertSee("Case #{$report->id}")
            ->call('markAllNotificationsRead');

        $this->assertSame(0, $investigator->unreadNotifications()->count());
    }
}
