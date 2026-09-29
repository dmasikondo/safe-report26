<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Show the anonymous submission form.
     */
    public function create(): View
    {
        return view('report.create');
    }

    /**
     * Store a new report.
     *
     * Tracking token is auto-generated in Report::booted().
     * No user identity is attached.
     */
    public function store(StoreReportRequest $request): RedirectResponse
    {
        $report = Report::create($request->validated());

        return redirect()
            ->route('report.submitted', ['token' => $report->tracking_token]);
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

        $token  = strtoupper(trim($request->input('token')));
        $report = Report::where('tracking_token', $token)->first();

        if (! $report) {
            return back()
                ->withInput()
                ->withErrors(['token' => 'No report found with that tracking token. Please check and try again.']);
        }

        return view('report.status', compact('report'));
    }
}
