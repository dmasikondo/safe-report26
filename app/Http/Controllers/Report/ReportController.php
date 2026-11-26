<?php

namespace App\Http\Controllers\Report;

use App\Actions\Reports\AutoAssignReport;
use App\Actions\Reports\StoreReportAttachment;
use App\Enums\ReportCategoryEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ReportController extends Controller
{
    /**
     * Show the anonymous submission form.
     */
    public function create(): View
    {
        return view('report.create', [
            'departments' => Department::ordered()->get(['id', 'name']),
        ]);
    }

    /**
     * Store a new report.
     *
     * Tracking token is auto-generated in Report::booted().
     * No user identity is attached. The report is then auto-assigned or
     * flagged to admins (see AutoAssignReport). JSON requests (the form's upload-progress
     * submit) receive the confirmation URL instead of a redirect.
     */
    public function store(StoreReportRequest $request, StoreReportAttachment $storeAttachment, AutoAssignReport $autoAssignReport): RedirectResponse|JsonResponse
    {
        $category = ReportCategoryEnum::from($request->validated('category'));
        $storedPaths = [];

        try {
            $report = DB::transaction(function () use ($request, $category, $storeAttachment, &$storedPaths): Report {
                $report = Report::create([
                    ...$request->safe()->except('attachments'),
                    'priority' => $category->defaultPriority(),
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $storedPaths[] = $storeAttachment->handle($report, $file)->path;
                }

                return $report;
            });
        } catch (Throwable $exception) {
            Storage::disk(config('safereport.attachments.disk'))->delete($storedPaths);

            throw $exception;
        }

        // The report is safely stored; a problem assigning it must not fail the reporter's submission.
        rescue(fn () => $autoAssignReport->handle($report));

        $confirmationUrl = route('report.submitted', ['token' => $report->tracking_token]);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $confirmationUrl]);
        }

        return redirect()->to($confirmationUrl);
    }

    /**
     * Confirmation page shown immediately after submission.
     * Displays the tracking token and instructs the reporter to save it.
     */
    public function submitted(string $token): View
    {
        // We look up the report so the page can show category / subject preview.
        // We never show body content on this page to minimise re-exposure risk.
        $report = Report::where('tracking_token', $token)->firstOrFail();

        return view('report.submitted', compact('report'));
    }

    /**
     * Show the token-entry page for tracking a report.
     */
    public function trackForm(): View
    {
        return view('report.track');
    }

    /**
     * Look up a report by tracking token and show its status.
     */
    public function track(Request $request): View|RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = strtoupper(trim($request->input('token')));
        $report = Report::where('tracking_token', $token)->first();

        if (! $report) {
            return back()
                ->withInput()
                ->withErrors(['token' => 'No report found with that tracking token. Please check and try again.']);
        }

        return view('report.status', compact('report'));
    }
}
