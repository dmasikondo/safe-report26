<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();

            // Encrypted file on the private disk. The original filename is never
            // stored because it can identify the reporter.
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');              // bytes, after metadata removal
            $table->char('sha256', 64);                   // fingerprint of the clean file
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_attachments');
    }
};
