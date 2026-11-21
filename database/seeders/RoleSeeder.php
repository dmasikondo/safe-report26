<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Iterate over every RoleEnum case and upsert the corresponding
         * database record. This means:
         *
         *  - Adding a new case to RoleEnum automatically seeds a new role
         *    on the next db:seed run.
         *  - Removing a case from RoleEnum does NOT delete the database
         *    record — that requires a separate migration to be safe.
         *  - Running db:seed multiple times is safe (updateOrCreate is idempotent).
         */
        foreach (RoleEnum::cases() as $case) {
            Role::updateOrCreate(
                ['name' => $case->value],
                [
                    'label' => $case->label(),
                    'description' => $case->description(),
                ],
            );
        }

        $seededRoles = array_column(RoleEnum::cases(), 'value');
        $this->command?->info('Roles seeded: '.implode(', ', $seededRoles));
    }
}
