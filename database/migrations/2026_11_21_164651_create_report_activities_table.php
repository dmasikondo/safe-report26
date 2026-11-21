<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case activity log: every staff action on a report (status change,
     * assignment, priority change, internal note) is recorded here.
     * Never shown to the reporter.
     */
    public function up(): void
    {
        Schema::create('report_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');                     // ReportActivityTypeEnum value
            $table->string('from_value')->nullable();   // previous status / assignee id / priority
            $table->string('to_value')->nullable();     // new status / assignee id / priority
            $table->text('body')->nullable();           // note text or reason for change
            $table->timestamps();

            $table->index(['report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_activities');
    }
};
