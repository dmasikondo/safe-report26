<?php

namespace Tests\Feature\Report;

use App\Enums\ReportCategoryEnum;
use App\Enums\ReportStatusEnum;
use App\Models\Department;
use App\Models\Report;
use App\Support\ReportEncrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'category' => ReportCategoryEnum::CORRUPTION->value,
            'subject' => 'Minister accepted cash bribes from contractor',
            'body' => str_repeat('Full detail about the incident. ', 10),
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
            'incident_date' => 'January 2026',
            'incident_location' => 'Harare CBD',
            'organisation_involved' => 'Ministry of Finance',
            'contact_hint' => 'signal:anon123',
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

    // ── Categories and departments ───────────────────────────────────────────

    public function test_sexual_harassment_reports_start_at_high_priority(): void
    {
        $this->post(route('report.store'), $this->validPayload([
            'category' => ReportCategoryEnum::SEXUAL_HARASSMENT->value,
        ]))->assertRedirect();

        $report = Report::first();
        $this->assertEquals(ReportCategoryEnum::SEXUAL_HARASSMENT, $report->category);
        $this->assertEquals(3, $report->priority);
    }

    public function test_form_lists_departments_alphabetically(): void
    {
        Department::factory()->create(['name' => 'Security']);
        Department::factory()->create(['name' => 'Applied Arts']);
        Department::factory()->create(['name' => 'Exams']);

        $this->get(route('report.create'))
            ->assertOk()
            ->assertSeeInOrder(['Applied Arts', 'Exams', 'Security']);
    }

    public function test_selected_department_is_stored(): void
    {
        $department = Department::factory()->create();

        $this->post(route('report.store'), $this->validPayload(['department_id' => $department->id]));

        $this->assertTrue(Report::first()->department->is($department));
    }

    public function test_department_must_exist(): void
    {
        $this->post(route('report.store'), $this->validPayload(['department_id' => 999]))
            ->assertSessionHasErrors('department_id');
    }

    // ── Encryption at rest ────────────────────────────────────────────────────

    public function test_report_content_is_encrypted_in_the_database(): void
    {
        $this->post(route('report.store'), $this->validPayload(['contact_hint' => 'signal:anon123']));

        $raw = DB::table('reports')->first();

        $this->assertNotSame('Minister accepted cash bribes from contractor', $raw->subject);
        $this->assertStringNotContainsString('anon123', $raw->contact_hint);
        $this->assertSame(
            'Minister accepted cash bribes from contractor',
            app(ReportEncrypter::class)->decryptString($raw->subject),
        );
    }

    // ── Attachments ───────────────────────────────────────────────────────────

    public function test_attachments_are_stored_encrypted_with_a_fingerprint(): void
    {
        Storage::fake('local');
        $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF";

        $this->post(route('report.store'), $this->validPayload([
            'attachments' => [
                UploadedFile::fake()->createWithContent('my-name-report.pdf', $pdf),
                UploadedFile::fake()->image('photo.png', 40, 40),
            ],
        ]))->assertRedirect();

        $attachments = Report::first()->attachments;
        $this->assertCount(2, $attachments);

        $stored = Storage::disk('local')->get($attachments[0]->path);
        $this->assertStringNotContainsString('%PDF', $stored);
        $this->assertSame($pdf, app(ReportEncrypter::class)->decryptString($stored));
        $this->assertSame(hash('sha256', $pdf), $attachments[0]->sha256);
        $this->assertSame('application/pdf', $attachments[0]->mime_type);
        $this->assertStringNotContainsString('my-name-report', $attachments[0]->path);
        $this->assertSame('image/png', $attachments[1]->mime_type);
    }

    public function test_photo_metadata_is_removed(): void
    {
        Storage::fake('local');

        $this->post(route('report.store'), $this->validPayload([
            'attachments' => [UploadedFile::fake()->createWithContent('photo.jpg', $this->jpegWithExif())],
        ]))->assertRedirect();

        $attachment = Report::first()->attachments->first();
        $clean = app(ReportEncrypter::class)->decryptString(Storage::disk('local')->get($attachment->path));

        $this->assertStringStartsWith("\xFF\xD8", $clean);
        $this->assertStringNotContainsString('Exif', $clean);
        $this->assertStringNotContainsString('SECRET-GPS-MARKER', $clean);
        $this->assertSame(hash('sha256', $clean), $attachment->sha256);
    }

    public function test_more_than_two_attachments_are_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('report.store'), $this->validPayload([
            'attachments' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ]))->assertSessionHasErrors('attachments');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_attachments_larger_than_two_megabytes_are_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('report.store'), $this->validPayload([
            'attachments' => [UploadedFile::fake()->image('big.jpg')->size(2049)],
        ]))->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_json_submission_returns_the_confirmation_url(): void
    {
        Storage::fake('local');

        $response = $this->postJson(route('report.store'), $this->validPayload([
            'attachments' => [UploadedFile::fake()->image('photo.png', 40, 40)],
        ]));

        $report = Report::first();
        $response->assertOk()
            ->assertExactJson(['redirect' => route('report.submitted', $report->tracking_token)]);
        $this->assertCount(1, $report->attachments);
    }

    public function test_json_submission_with_an_oversized_attachment_returns_validation_errors(): void
    {
        Storage::fake('local');

        $this->postJson(route('report.store'), $this->validPayload([
            'attachments' => [UploadedFile::fake()->image('big.jpg')->size(2049)],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('attachments.0');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_unsupported_file_types_are_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('report.store'), $this->validPayload([
            'attachments' => [
                UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            ],
        ]))->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('reports', 0);
    }

    /**
     * A small JPEG with an EXIF (APP1) segment carrying a recognisable marker.
     */
    private function jpegWithExif(): string
    {
        $image = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();

        $payload = "Exif\x00\x00MM\x00\x2A\x00\x00\x00\x08\x00\x00\x00\x00\x00\x00SECRET-GPS-MARKER";
        $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
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
