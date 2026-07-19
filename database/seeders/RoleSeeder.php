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
                    'label'       => $this->labelFor($case),
                    'description' => $this->descriptionFor($case),
                ],
            );
        }

        $seededRoles = array_column(RoleEnum::cases(), 'value');
        $this->command->info('Roles seeded: ' . implode(', ', $seededRoles));
    }

    // ── Labels and descriptions ───────────────────────────────────────────
    // Defined here rather than on the enum itself to keep the enum lean.
    // If you later add label()/description() methods to RoleEnum,
    // you can replace these with $case->label() and $case->description().

    private function labelFor(RoleEnum $role): string
    {
        return match ($role) {
            RoleEnum::SUPERADMIN   => 'Super Administrator',
            RoleEnum::ADMIN        => 'Administrator',
            RoleEnum::SUPERVISOR   => 'Supervisor',
            RoleEnum::INVESTIGATOR => 'Investigator',
        };
    }

    private function descriptionFor(RoleEnum $role): string
    {
        return match ($role) {
            RoleEnum::SUPERADMIN   => 'Full system access including audit log review and user management.',
            RoleEnum::ADMIN        => 'Manages staff accounts and system configuration.',
            RoleEnum::SUPERVISOR   => 'Oversees investigators; can escalate or reassign cases.',
            RoleEnum::INVESTIGATOR => 'Receives, reviews, and manages assigned cases.',
        };
    }
}
