<?php

use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Staff\ReportAttachmentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// ── Anonymous report submission (no auth) ─────────────────────────────────────
Route::prefix('report')->name('report.')->group(function () {
    Route::get('submit', [ReportController::class, 'create'])->name('create');
    Route::post('submit', [ReportController::class, 'store'])->name('store');
    Route::get('submitted/{token}', [ReportController::class, 'submitted'])->name('submitted');
    Route::get('track', [ReportController::class, 'trackForm'])->name('track');
    Route::post('track', [ReportController::class, 'track'])->name('track.submit');
});

// ── Authenticated staff area ──────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::prefix('staff')->name('staff.')->group(function () {
        // Case handling — supervisors see all cases, investigators their own
        Route::livewire('reports', 'pages::staff.reports.index')->name('reports.index');
        Route::livewire('reports/{report}', 'pages::staff.reports.show')->name('reports.show');
        Route::get('reports/{report}/attachments/{attachment}', [ReportAttachmentController::class, 'show'])
            ->scopeBindings()
            ->name('reports.attachments.show');

        // Staff account management — superadmins and admins
        Route::livewire('users', 'pages::staff.users.index')->name('users.index');
        Route::livewire('users/create', 'pages::staff.users.create')->name('users.create');
    });
});

require __DIR__.'/settings.php';
