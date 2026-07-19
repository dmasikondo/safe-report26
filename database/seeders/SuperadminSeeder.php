<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Default superadmin account ────────────────────────────────────
        // IMPORTANT: Change these credentials immediately after first login.
        // Do NOT deploy to production without changing email and password.

        $superAdmin = User::updateOrCreate(
            ['email' => 'dmasikondo@gmail.com'],
            [
                'first_name'      => 'System',
                'last_name'      => 'Administrator',
                'password'  => 'password',
                'is_active' => true,
            ],
        );

        $superAdminRole = Role::where('name', 'superadmin')->firstOrFail();

        if (! $superAdmin->hasRole('superadmin')) {
            $superAdmin->roles()->attach($superAdminRole);
        }

        $this->command->info('Superadmin seeded: admin@safereport.zw / password');
        $this->command->warn('⚠  Change the default password before deploying to production.');
    }
}