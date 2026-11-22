<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The separate "bribery" category was merged into "Bribery & Corruption".
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('reports')->where('category', 'bribery')->update(['category' => 'corruption']);
    }

    /**
     * Reverse the migrations. Merged rows cannot be told apart, so they stay merged.
     */
    public function down(): void
    {
        //
    }
};
