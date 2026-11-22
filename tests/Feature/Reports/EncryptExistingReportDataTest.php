<?php

namespace Tests\Feature\Reports;

use App\Actions\Reports\EncryptExistingReportData;
use App\Models\Report;
use App\Models\ReportActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptExistingReportDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate a report saved before encryption was introduced.
     */
    private function plainTextReport(): Report
    {
        $report = Report::factory()->create();
        $activity = ReportActivity::factory()->for($report)->create();

        DB::table('reports')->where('id', $report->id)->update([
            'subject' => 'Leaked exam papers sold',
            'contact_hint' => null,
        ]);
        DB::table('report_activities')->where('id', $activity->id)->update(['body' => 'Called the exams office.']);

        return $report;
    }

    public function test_plain_text_rows_are_encrypted(): void
    {
        $report = $this->plainTextReport();

        app(EncryptExistingReportData::class)->encrypt();

        $this->assertNotSame('Leaked exam papers sold', DB::table('reports')->value('subject'));
        $this->assertNull(DB::table('reports')->value('contact_hint'));
        $this->assertSame('Leaked exam papers sold', $report->fresh()->subject);
        $this->assertSame('Called the exams office.', ReportActivity::first()->body);
    }

    public function test_running_twice_leaves_encrypted_rows_unchanged(): void
    {
        $this->plainTextReport();
        $action = app(EncryptExistingReportData::class);

        $action->encrypt();
        $ciphertext = DB::table('reports')->value('subject');

        $this->assertSame(0, $action->encrypt());
        $this->assertSame($ciphertext, DB::table('reports')->value('subject'));
    }

    public function test_decrypt_restores_plain_text(): void
    {
        $this->plainTextReport();
        $action = app(EncryptExistingReportData::class);

        $action->encrypt();
        $action->decrypt();

        $this->assertSame('Leaked exam papers sold', DB::table('reports')->value('subject'));
        $this->assertSame('Called the exams office.', DB::table('report_activities')->value('body'));
    }
}
