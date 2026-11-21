<?php

namespace Database\Factories;

use App\Enums\ReportActivityTypeEnum;
use App\Models\Report;
use App\Models\ReportActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportActivity>
 */
class ReportActivityFactory extends Factory
{
    protected $model = ReportActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'user_id' => User::factory(),
            'type' => ReportActivityTypeEnum::NOTE_ADDED,
            'body' => fake()->paragraph(),
        ];
    }
}
