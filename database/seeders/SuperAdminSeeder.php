<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the first superadmin from the SAFEREPORT_ADMIN_* settings.
     *
     * An existing account is left untouched, so re-seeding never resets a
     * password the administrator has already changed.
     */
    public function run(): void
    {
        $email = config('safereport.super_admin.email');
        $configuredPassword = config('safereport.super_admin.password');
        $password = filled($configuredPassword) ? $configuredPassword : Str::password(20, symbols: false);

        $superAdmin = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'password' => $password,
                'is_active' => true,
            ],
        );

        $superAdminRole = Role::where('name', 'superadmin')->firstOrFail();

        if (! $superAdmin->hasRole('superadmin')) {
            $superAdmin->roles()->attach($superAdminRole);
        }

        if (! $superAdmin->wasRecentlyCreated) {
            $this->command->info("Superadmin {$email} already exists; its password was not changed.");

            return;
        }

        if (filled($configuredPassword)) {
            $this->command->info("Superadmin created: {$email} (password from SAFEREPORT_ADMIN_PASSWORD).");

            return;
        }

        $this->command->info("Superadmin created: {$email}");
        $this->command->warn("Generated password: {$password}");
        $this->command->warn('Copy it now, it will not be shown again. Change it after your first login.');
    }
}
