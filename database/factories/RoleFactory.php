<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Since RoleEnum defines a fixed set of four roles,
     * the factory picks a random case by default.
     *
     * For a specific role use the named states below:
     *   Role::factory()->superAdmin()->create()
     *   Role::factory()->investigator()->create()
     */
    public function definition(): array
    {
        $role = $this->faker->randomElement(RoleEnum::cases());

        return $this->definitionForEnum($role);
    }

    // ── Named states — one per enum case ─────────────────────────────────

    public function superAdmin(): static
    {
        return $this->state($this->definitionForEnum(RoleEnum::SUPERADMIN));
    }

    public function admin(): static
    {
        return $this->state($this->definitionForEnum(RoleEnum::ADMIN));
    }

    public function supervisor(): static
    {
        return $this->state($this->definitionForEnum(RoleEnum::SUPERVISOR));
    }

    public function investigator(): static
    {
        return $this->state($this->definitionForEnum(RoleEnum::INVESTIGATOR));
    }

    // ── Private helper ────────────────────────────────────────────────────

    /**
     * Build the attribute array for a given enum case.
     * Label and description are derived from the enum itself,
     * keeping the factory and enum as the single source of truth.
     */
    private function definitionForEnum(RoleEnum $role): array
    {
        return [
            'name' => $role,            // the cast handles ->value automatically
            'label' => $role->label(),
            'description' => $role->description(),
        ];
    }
}
