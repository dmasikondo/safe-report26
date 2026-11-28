<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A random, non-sequential identifier used in case URLs so links do not
     * expose (or let anyone step through) the numeric report ids.
     * Existing reports are given one before the column becomes required.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('reports')->whereNull('uuid')->lazyById()->each(function (object $report): void {
            DB::table('reports')->where('id', $report->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
