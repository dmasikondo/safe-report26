<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Reports\ReadReportAttachment;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ReportAttachmentController extends Controller
{
    /**
     * Decrypt an evidence file and send it as a download. It is never
     * rendered inline, so a crafted file cannot run in the staff browser.
     */
    public function show(Report $report, ReportAttachment $attachment, ReadReportAttachment $readAttachment): Response
    {
        Gate::authorize('view', $report);

        $position = $report->attachments()->where('id', '<=', $attachment->id)->count();

        return response()->streamDownload(
            function () use ($readAttachment, $attachment): void {
                echo $readAttachment->handle($attachment);
            },
            $attachment->downloadName($position),
            [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => 'sandbox',
                'Cache-Control' => 'no-store, private',
            ],
        );
    }
}
