<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Harare Polytechnic departments. Safe to run repeatedly.
     *
     * @var list<string>
     */
    public const DEPARTMENTS = [
        'Administration',
        'Finance',
        'Human Resources',
        'Procurement',
        'Grounds',
        'Hostels',
        'Canteen',
        'Security',
        'Central Maintenance',
        'Exams',
        'Applied Arts',
        'Commerce',
        'Library and Information Science',
        'Office Management',
        'Tourism and Hospitality',
        'Science Technology',
        'Printing and Graphic Arts',
        'Mass Communication',
        'Electrical Engineering',
        'Mechanical Engineering',
        'Construction Engineering',
        'Automotive Engineering',
        'Civil Engineering',
        'Information and Communication Technology',
    ];

    public function run(): void
    {
        foreach (self::DEPARTMENTS as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        $this->command?->info(count(self::DEPARTMENTS).' departments seeded.');
    }
}
