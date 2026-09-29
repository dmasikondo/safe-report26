<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // ── Tracking ──────────────────────────────────────────────────
            // 32-char hex token given to the reporter to check status.
            // No user FK — submissions are structurally anonymous.
            $table->string('tracking_token', 64)->unique()->index();

            // ── Classification ────────────────────────────────────────────
            $table->string('category');          // ReportCategoryEnum value
            $table->string('status')->default('submitted'); // ReportStatusEnum value

            // ── Content (Phase 2: these columns will be AES-256-GCM encrypted) ──
            $table->text('subject');
            $table->longText('body');
            $table->string('incident_date')->nullable();    // stored as string; reporter may be vague
            $table->string('incident_location')->nullable();
            $table->string('organisation_involved')->nullable();

            // ── Reporter contact (optional, anonymous by default) ─────────
            // Reporter may optionally provide a contact method for follow-up.
            // Phase 2: this field will be encrypted at rest.
            $table->string('contact_hint')->nullable();    // e.g. "email: anon@proton.me"

            // ── Staff assignment ──────────────────────────────────────────
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ── Staff notes (internal, never shown to reporter) ───────────
            $table->text('internal_notes')->nullable();

            // ── Priority ─────────────────────────────────────────────────
            $table->unsignedTinyInteger('priority')->default(2); // 1=low 2=medium 3=high

            // ── Soft delete + timestamps ──────────────────────────────────
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
