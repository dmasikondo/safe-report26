<?php

namespace Tests\Feature;

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_the_three_main_actions(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('SafeReport')
            ->assertSee('href="'.route('report.create').'"', escape: false)
            ->assertSee('href="'.route('report.track').'"', escape: false)
            ->assertSee('href="'.route('login').'"', escape: false)
            ->assertSee('action="'.route('report.track.submit').'"', escape: false);
    }

    public function test_quick_track_form_shows_the_status_for_a_valid_token(): void
    {
        $report = Report::factory()->create();

        $this->post(route('report.track.submit'), ['token' => $report->tracking_token])
            ->assertOk()
            ->assertViewIs('report.status');
    }

    public function test_unknown_token_from_the_homepage_returns_to_the_homepage_with_an_error(): void
    {
        $this->from(route('home'))
            ->post(route('report.track.submit'), ['token' => 'AAAAAAAA-BBBBBBBB'])
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertSee('No report found with that tracking token. Please check and try again.');
    }
}
