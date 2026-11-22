<?php

namespace Tests\Feature\Staff;

use App\Actions\Reports\StoreReportAttachment;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportAttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    private function attachmentFor(Report $report): ReportAttachment
    {
        return app(StoreReportAttachment::class)->handle(
            $report,
            UploadedFile::fake()->createWithContent('evidence.pdf', self::PDF),
        );
    }

    public function test_supervisor_downloads_the_decrypted_file(): void
    {
        $supervisor = User::factory()->withRole(RoleEnum::SUPERVISOR)->create();
        $report = Report::factory()->create();
        $attachment = $this->attachmentFor($report);

        $response = $this->actingAs($supervisor)
            ->get(route('staff.reports.attachments.show', [$report, $attachment]))
            ->assertOk()
            ->assertDownload('evidence-1.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', 'sandbox');

        $this->assertSame(self::PDF, $response->streamedContent());
    }

    public function test_assigned_investigator_can_download(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->assignedTo($investigator)->create();
        $attachment = $this->attachmentFor($report);

        $this->actingAs($investigator)
            ->get(route('staff.reports.attachments.show', [$report, $attachment]))
            ->assertOk();
    }

    public function test_unassigned_investigator_is_forbidden(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $report = Report::factory()->create();
        $attachment = $this->attachmentFor($report);

        $this->actingAs($investigator)
            ->get(route('staff.reports.attachments.show', [$report, $attachment]))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $report = Report::factory()->create();
        $attachment = $this->attachmentFor($report);

        $this->get(route('staff.reports.attachments.show', [$report, $attachment]))
            ->assertRedirect(route('login'));
    }

    public function test_attachment_from_another_report_is_not_found(): void
    {
        $investigator = User::factory()->withRole(RoleEnum::INVESTIGATOR)->create();
        $myReport = Report::factory()->assignedTo($investigator)->create();
        $otherAttachment = $this->attachmentFor(Report::factory()->create());

        $this->actingAs($investigator)
            ->get(route('staff.reports.attachments.show', [$myReport, $otherAttachment]))
            ->assertNotFound();
    }
}
