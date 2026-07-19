<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'        => fake()->firstName(),
            'last_name'         => fake()->lastName(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_active'         => true,
            'last_login_at'     => null,
        ];
    }

    // ── States ────────────────────────────────────────────────────────────

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Create a user and assign a role after creation.
     *
     * The role must already exist in the database — either seeded or
     * created in the test's arrange step.
     *
     * Usage:
     *   User::factory()->withRole(RoleEnum::Investigator)->create()
     *   User::factory()->withRole(RoleEnum::Admin)->create()
     */
    public function withRole(RoleEnum $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $roleModel = Role::where('name', $role->value)->firstOrFail();
            $user->roles()->attach($roleModel);
        });
    }
}