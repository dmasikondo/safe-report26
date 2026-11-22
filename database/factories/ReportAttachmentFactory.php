<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Creates the database record only; tests that need the encrypted file on
 * disk should go through StoreReportAttachment instead.
 *
 * @extends Factory<ReportAttachment>
 */
class ReportAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'path' => 'reports/'.Str::uuid().'.bin',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'sha256' => hash('sha256', Str::random()),
        ];
    }
}
