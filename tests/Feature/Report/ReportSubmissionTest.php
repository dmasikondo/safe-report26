<?php

namespace Tests\Feature\Report;

use App\Enums\ReportCategoryEnum;
use App\Enums\ReportStatusEnum;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'category' => ReportCategoryEnum::CORRUPTION->value,
            'subject'  => 'Minister accepted cash bribes from contractor',
            'body'     => str_repeat('Full detail about the incident. ', 10),
        ], $overrides);
    }

    // ── Submission form ───────────────────────────────────────────────────────

    public function test_submission_form_is_accessible_without_auth(): void
    {
        $this->get(route('report.create'))
            ->assertOk()
            ->assertSee('Submit a report');
    }

    public function test_valid_report_is_stored_and_redirects_to_confirmation(): void
    {
        $response = $this->post(route('report.store'), $this->validPayload());

        $report = Report::first();
        $this->assertNotNull($report);

        $response->assertRedirect(route('report.submitted', $report->tracking_token));
    }

    public function test_tracking_token_is_auto_generated_in_expected_format(): void
    {
        $this->post(route('report.store'), $this->validPayload());

        $token = Report::first()->tracking_token;

        // Format: XXXXXXXX-XXXXXXXX (two 8-char uppercase alphanumeric segments)
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}-[A-Z0-9]{8}$/', $token);
    }

    public function test_report_defaults_to_submitted_status_and_medium_priority(): void
    {
        $this->post(route('report.store'), $this->validPayload());

        $report = Report::first();
        $this->assertEquals(ReportStatusEnum::SUBMITTED, $report->status);
        $this->assertEquals(2, $report->priority);
    }

    public function test_optional_fields_are_stored_when_provided(): void
    {
        $payload = $this->validPayload([
            'incident_date'       => 'January 2026',
            'incident_location'   => 'Harare CBD',
            'organisation_involved' => 'Ministry of Finance',
            'contact_hint'        => 'signal:anon123',
        ]);

        $this->post(route('report.store'), $payload);

        $report = Report::first();
        $this->assertEquals('January 2026', $report->incident_date);
        $this->assertEquals('Harare CBD', $report->incident_location);
        $this->assertEquals('Ministry of Finance', $report->organisation_involved);
        $this->assertEquals('signal:anon123', $report->contact_hint);
    }

    public function test_report_has_no_user_association(): void
    {
        $this->post(route('report.store'), $this->validPayload());

        $this->assertNull(Report::first()->assigned_to);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_category_is_required(): void
    {
        $this->post(route('report.store'), $this->validPayload(['category' => '']))
            ->assertSessionHasErrors('category');
    }

    public function test_category_must_be_valid_enum_value(): void
    {
        $this->post(route('report.store'), $this->validPayload(['category' => 'invalid_category']))
            ->assertSessionHasErrors('category');
    }

    public function test_subject_is_required(): void
    {
        $this->post(route('report.store'), $this->validPayload(['subject' => '']))
            ->assertSessionHasErrors('subject');
    }

    public function test_subject_must_be_at_least_10_characters(): void
    {
        $this->post(route('report.store'), $this->validPayload(['subject' => 'Too short']))
            ->assertSessionHasErrors('subject');
    }

    public function test_subject_max_length_is_200(): void
    {
        $this->post(route('report.store'), $this->validPayload(['subject' => str_repeat('a', 201)]))
            ->assertSessionHasErrors('subject');
    }

    public function test_body_is_required(): void
    {
        $this->post(route('report.store'), $this->validPayload(['body' => '']))
            ->assertSessionHasErrors('body');
    }

    public function test_body_must_be_at_least_50_characters(): void
    {
        $this->post(route('report.store'), $this->validPayload(['body' => 'Too short body']))
            ->assertSessionHasErrors('body');
    }

    // ── Confirmation page ─────────────────────────────────────────────────────

    public function test_confirmation_page_shows_tracking_token(): void
    {
        $this->post(route('report.store'), $this->validPayload());

        $token = Report::first()->tracking_token;

        $this->get(route('report.submitted', $token))
            ->assertOk()
            ->assertSee($token);
    }

    public function test_confirmation_page_404s_for_unknown_token(): void
    {
        $this->get(route('report.submitted', 'XXXXXXXX-XXXXXXXX'))
            ->assertNotFound();
    }
}
