<?php

use App\Http\Controllers\Report\ReportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// ── Anonymous report submission (no auth) ─────────────────────────────────────
Route::prefix('report')->name('report.')->group(function () {
    Route::get('submit',           [ReportController::class, 'create'])->name('create');
    Route::post('submit',          [ReportController::class, 'store'])->name('store');
    Route::get('submitted/{token}',[ReportController::class, 'submitted'])->name('submitted');
    Route::get('track',            [ReportController::class, 'trackForm'])->name('track');
    Route::post('track',           [ReportController::class, 'track'])->name('track.submit');
});

// ── Authenticated staff area ──────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
