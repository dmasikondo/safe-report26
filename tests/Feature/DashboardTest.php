<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

        Livewire::test('pages::settings.case-assignment')
            ->assertSee('Automatic case assignment')
            ->set('autoAssign', false);
        $this->assertFalse(Setting::autoAssignEnabled());

        Livewire::test('pages::settings.case-assignment')
            ->assertSet('autoAssign', false)
            ->set('autoAssign', true);
        $this->assertTrue(Setting::autoAssignEnabled());
    }

    public function test_supervisors_cannot_change_automatic_assignment(): void
    {
        $this->seed(RoleSeeder::class);
        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERVISOR)->create());

        $this->get(route('case-assignment.edit'))->assertForbidden();

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee(route('case-assignment.edit'));

        $this->assertTrue(Setting::autoAssignEnabled());
    }

    public function test_settings_menu_offers_case_assignment_to_admins(): void
    {
        $this->seed(RoleSeeder::class);
        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create());

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Organisation')
            ->assertSee(route('case-assignment.edit'));
    }

    public function test_dashboard_shows_automatic_assignment_status_to_admins_only(): void
    {
        $this->seed(RoleSeeder::class);
        Setting::put(Setting::AUTO_ASSIGN_CASES, false);

        $this->actingAs(User::factory()->withRole(RoleEnum::ADMIN)->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Automatic assignment:', 'Off'])
            ->assertSee(route('case-assignment.edit'));

        $this->actingAs(User::factory()->withRole(RoleEnum::SUPERVISOR)->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Automatic assignment:');
    }

    public function test_unread_notifications_are_listed_and_can_be_marked_read(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $investigator->notify(new CaseAssignedNotification($report, $investigator));

        $this->actingAs($investigator);

        Livewire::test('pages::dashboard')
            ->assertSee("Case {$report->reference}")
            ->call('markAllNotificationsRead');

        $this->assertSame(0, $investigator->unreadNotifications()->count());
    }

    public function test_notifications_store_no_link_and_never_reveal_the_tracking_token(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $investigator->notify(new CaseAssignedNotification($report, $investigator));

        $data = $investigator->notifications()->first()->data;

        $this->assertSame(['report_uuid', 'message'], array_keys($data));
        $this->assertSame($report->uuid, $data['report_uuid']);
        $this->assertStringContainsString($report->reference, $data['message']);
        $this->assertStringNotContainsString($report->tracking_token, $data['message']);
        $this->assertStringNotContainsString("#{$report->id}", $data['message']);
    }

    public function test_opening_a_notification_marks_it_read_and_goes_to_the_case(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $investigator->notify(new CaseAssignedNotification($report, $investigator));
        $notification = $investigator->notifications()->first();

        $this->actingAs($investigator);

        Livewire::test('pages::dashboard')
            ->call('openNotification', $notification->id)
            ->assertRedirect(route('staff.reports.show', $report));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notifications_saved_with_an_old_numeric_link_still_open_the_case(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $investigator->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => CaseAssignedNotification::class,
            'data' => [
                'report_id' => $report->id,
                'message' => "Case #{$report->id} has been assigned to you.",
                'url' => url("/staff/reports/{$report->id}"),
            ],
        ]);

        $this->actingAs($investigator);

        Livewire::test('pages::dashboard')
            ->call('openNotification', $investigator->notifications()->first()->id)
            ->assertRedirect(route('staff.reports.show', $report));
    }

    public function test_stored_numeric_case_links_are_converted_to_uuids(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $notification = $investigator->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => CaseAssignedNotification::class,
            'data' => [
                'report_id' => $report->id,
                'message' => 'A case has been assigned to you.',
                'url' => url("/staff/reports/{$report->id}"),
            ],
        ]);

        (require database_path('migrations/2026_11_28_100811_convert_notification_report_ids_to_uuids.php'))->up();

        $this->assertSame(
            ['report_uuid' => $report->uuid, 'message' => 'A case has been assigned to you.'],
            $notification->fresh()->data,
        );
    }

    public function test_a_notification_for_a_deleted_case_returns_to_the_dashboard(): void
    {
        $this->seed(RoleSeeder::class);
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $investigator->notify(new CaseAssignedNotification($report, $investigator));
        $report->delete();

        $this->actingAs($investigator);

        Livewire::test('pages::dashboard')
            ->call('openNotification', $investigator->notifications()->first()->id)
            ->assertRedirect(route('dashboard'));
    }
}
