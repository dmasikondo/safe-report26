<?php

use App\Actions\Reports\EncryptExistingReportData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts report content at rest. Ciphertext is much longer than the
 * original text, so short string columns are widened to text first.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->text('incident_date')->nullable()->change();
            $table->text('incident_location')->nullable()->change();
            $table->text('organisation_involved')->nullable()->change();
            $table->text('contact_hint')->nullable()->change();
        });

        app(EncryptExistingReportData::class)->encrypt();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(EncryptExistingReportData::class)->decrypt();

        Schema::table('reports', function (Blueprint $table) {
            $table->string('incident_date')->nullable()->change();
            $table->string('incident_location')->nullable()->change();
            $table->string('organisation_involved')->nullable()->change();
            $table->string('contact_hint')->nullable()->change();
        });
    }
};
