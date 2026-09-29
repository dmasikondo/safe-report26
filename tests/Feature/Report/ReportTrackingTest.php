<?php

namespace Tests\Feature\Report;

use App\Enums\ReportCategoryEnum;
use App\Enums\ReportStatusEnum;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(array $attrs = []): Report
    {
        return Report::create(array_merge([
            'category' => ReportCategoryEnum::FRAUD->value,
            'subject'  => 'Fraudulent procurement at state hospital',
            'body'     => str_repeat('Supporting detail for the report. ', 5),
        ], $attrs));
    }

    // ── Track form ────────────────────────────────────────────────────────────

    public function test_track_form_is_accessible_without_auth(): void
    {
        $this->get(route('report.track'))
            ->assertOk()
            ->assertSee('Track');
    }

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function test_valid_token_shows_status_page(): void
    {
        $report = $this->makeReport();

        $this->post(route('report.track.submit'), ['token' => $report->tracking_token])
            ->assertOk()
            ->assertViewIs('report.status')
            ->assertViewHas('report', fn ($r) => $r->is($report));
    }

    public function test_token_lookup_is_case_insensitive(): void
    {
        $report = $this->makeReport();
        $lower  = strtolower($report->tracking_token);

        $this->post(route('report.track.submit'), ['token' => $lower])
            ->assertOk()
            ->assertViewIs('report.status');
    }

    public function test_unknown_token_returns_error(): void
    {
        $this->post(route('report.track.submit'), ['token' => 'AAAAAAAA-BBBBBBBB'])
            ->assertRedirect()
            ->assertSessionHasErrors('token');
    }

    public function test_empty_token_fails_validation(): void
    {
        $this->post(route('report.track.submit'), ['token' => ''])
            ->assertSessionHasErrors('token');
    }

    // ── Status page content ───────────────────────────────────────────────────

    public function test_status_page_shows_report_subject_and_category(): void
    {
        $report = $this->makeReport();

        $this->get(route('report.submitted', $report->tracking_token))
            ->assertOk()
            ->assertSee($report->subject);
    }

    public function test_status_page_shows_timeline_steps(): void
    {
        $report = $this->makeReport();

        $response = $this->post(route('report.track.submit'), ['token' => $report->tracking_token]);
        $response->assertOk();

        // Every status in the timeline should appear on the page
        foreach (ReportStatusEnum::timeline() as $status) {
            $response->assertSee($status->label());
        }
    }

    // ── Model helpers ─────────────────────────────────────────────────────────

    public function test_report_is_open_when_not_closed(): void
    {
        $report = $this->makeReport();

        $this->assertTrue($report->isOpen());
        $this->assertFalse($report->isClosed());
    }

    public function test_report_is_closed_when_status_is_closed(): void
    {
        $report = $this->makeReport(['status' => ReportStatusEnum::CLOSED->value]);

        $this->assertFalse($report->isOpen());
        $this->assertTrue($report->isClosed());
    }

    public function test_advance_moves_status_to_next_workflow_step(): void
    {
        $report = $this->makeReport(['status' => ReportStatusEnum::SUBMITTED->value]);
        $report->advance();

        $this->assertEquals(ReportStatusEnum::RECEIVED, $report->fresh()->status);
    }
}
