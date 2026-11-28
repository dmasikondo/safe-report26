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
     * A random, non-sequential identifier used in staff profile URLs so links
     * do not expose (or let anyone step through) the numeric user ids.
     * Existing accounts are given one before the column becomes required.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('users')->whereNull('uuid')->lazyById()->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
