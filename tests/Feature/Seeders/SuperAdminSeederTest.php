<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_creates_superadmin_with_configured_email_and_password(): void
    {
        config(['safereport.super_admin' => [
            'email' => 'owner@institution.test',
            'password' => 'configured-secret-123',
        ]]);

        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::where('email', 'owner@institution.test')->firstOrFail();
        $this->assertTrue(Hash::check('configured-secret-123', $superAdmin->password));
        $this->assertTrue($superAdmin->hasRole('superadmin'));
    }

    public function test_prints_a_generated_password_that_works_when_none_is_configured(): void
    {
        config(['safereport.super_admin' => [
            'email' => 'owner@institution.test',
            'password' => null,
        ]]);

        Artisan::call('db:seed', ['--class' => SuperAdminSeeder::class]);
        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/Generated password: (\S+)/', $output);
        preg_match('/Generated password: (\S+)/', $output, $matches);

        $superAdmin = User::where('email', 'owner@institution.test')->firstOrFail();
        $this->assertTrue(Hash::check($matches[1], $superAdmin->password));
    }

    public function test_does_not_change_the_password_of_an_existing_account(): void
    {
        $existing = User::factory()->create([
            'email' => 'owner@institution.test',
            'password' => 'changed-after-login',
        ]);
        config(['safereport.super_admin' => [
            'email' => 'owner@institution.test',
            'password' => 'configured-secret-123',
        ]]);

        $this->seed(SuperAdminSeeder::class);

        $this->assertTrue(Hash::check('changed-after-login', $existing->fresh()->password));
        $this->assertTrue($existing->fresh()->hasRole('superadmin'));
    }
}
