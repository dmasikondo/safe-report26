<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoStaffSeeder extends Seeder
{
    /**
     * Demo supervisors, as [first name, last name].
     *
     * @var list<array{0: string, 1: string}>
     */
    public const SUPERVISORS = [
        ['Chipo', 'Mutasa'],
        ['Farai', 'Ndlovu'],
    ];

    /**
     * Demo investigators, as [first name, last name].
     *
     * @var list<array{0: string, 1: string}>
     */
    public const INVESTIGATORS = [
        ['Tendai', 'Moyo'],
        ['Rudo', 'Ncube'],
        ['Tatenda', 'Chikwanha'],
        ['Nyasha', 'Sibanda'],
        ['Kudakwashe', 'Mhlanga'],
        ['Rutendo', 'Makoni'],
        ['Tinashe', 'Dube'],
        ['Vimbai', 'Chirwa'],
        ['Tafadzwa', 'Mapfumo'],
        ['Chiedza', 'Nyathi'],
        ['Simbarashe', 'Gumbo'],
        ['Fadzai', 'Mukanya'],
    ];

    public const PASSWORD = 'password';

    /**
     * Local demo accounts: supervisors and investigators with the password
     * "password", spread across departments and not yet in any team.
     * Safe to run repeatedly; existing accounts are left as they are.
     */
    public function run(): void
    {
        $departments = collect(DepartmentSeeder::DEPARTMENTS)
            ->map(fn (string $name): Department => Department::firstOrCreate(['name' => $name]))
            ->values();

        $staff = [
            ...array_map(fn (array $name): array => [$name, RoleEnum::SUPERVISOR], self::SUPERVISORS),
            ...array_map(fn (array $name): array => [$name, RoleEnum::INVESTIGATOR], self::INVESTIGATORS),
        ];

        foreach ($staff as $index => [[$firstName, $lastName], $role]) {
            $user = User::firstOrCreate(
                ['email' => strtolower("{$firstName}.{$lastName}@safereport.test")],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => self::PASSWORD,
                    'department_id' => $departments[$index % $departments->count()]->id,
                    'is_active' => true,
                ],
            );

            if ($user->wasRecentlyCreated) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->assignRole($role->value);
        }

        $this->command?->info(count(self::SUPERVISORS).' supervisors and '.count(self::INVESTIGATORS).' investigators seeded (password: '.self::PASSWORD.').');
    }
}
