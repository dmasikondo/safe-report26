<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A short, random case reference (e.g. WB-7K3QXM) that staff use to refer to
     * a case in notifications and conversation. Unlike the numeric id it reveals
     * nothing about case volume, and unlike the tracking token it gives no
     * access to the public tracking page. Existing reports are given one
     * before the column becomes required.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('reference', 16)->nullable()->after('uuid');
        });

        $used = [];

        DB::table('reports')->whereNull('reference')->lazyById()->each(function (object $report) use (&$used): void {
            do {
                $reference = $this->generateReference();
            } while (isset($used[$reference]));

            $used[$reference] = true;

            DB::table('reports')->where('id', $report->id)->update(['reference' => $reference]);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('reference', 16)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }

    /**
     * Kept local to the migration so it keeps working if the model changes later.
     */
    private function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return 'WB-'.$code;
    }
};
