<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Who may create which role.
     *
     * @return array<string, array{RoleEnum, RoleEnum, bool}>
     */
    public static function roleCreationMatrix(): array
    {
        return [
            'superadmin creates superadmin' => [RoleEnum::SUPERADMIN, RoleEnum::SUPERADMIN, true],
            'superadmin creates admin' => [RoleEnum::SUPERADMIN, RoleEnum::ADMIN, true],
            'superadmin creates supervisor' => [RoleEnum::SUPERADMIN, RoleEnum::SUPERVISOR, true],
            'superadmin creates investigator' => [RoleEnum::SUPERADMIN, RoleEnum::INVESTIGATOR, true],
            'admin creates superadmin' => [RoleEnum::ADMIN, RoleEnum::SUPERADMIN, false],
            'admin creates admin' => [RoleEnum::ADMIN, RoleEnum::ADMIN, false],
            'admin creates supervisor' => [RoleEnum::ADMIN, RoleEnum::SUPERVISOR, true],
            'admin creates investigator' => [RoleEnum::ADMIN, RoleEnum::INVESTIGATOR, true],
            'supervisor creates investigator' => [RoleEnum::SUPERVISOR, RoleEnum::INVESTIGATOR, false],
            'investigator creates investigator' => [RoleEnum::INVESTIGATOR, RoleEnum::INVESTIGATOR, false],
        ];
    }

    #[DataProvider('roleCreationMatrix')]
    public function test_staff_creation_is_limited_by_role(RoleEnum $creatorRole, RoleEnum $newRole, bool $allowed): void
    {
        $creator = User::factory()->withRole($creatorRole)->create();

        $this->assertSame($allowed, $creator->can('createWithRole', [User::class, $newRole]));
    }

    public function test_only_superadmins_and_admins_can_open_staff_management(): void
    {
        foreach ([RoleEnum::SUPERADMIN, RoleEnum::ADMIN] as $role) {
            $this->assertTrue(User::factory()->withRole($role)->create()->can('create', User::class));
        }

        foreach ([RoleEnum::SUPERVISOR, RoleEnum::INVESTIGATOR] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->assertFalse($user->can('create', User::class));
            $this->assertFalse($user->can('viewAny', User::class));
        }
    }

    public function test_admin_can_deactivate_supervisors_and_investigators_but_not_admins(): void
    {
        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create();

        $this->assertTrue($admin->can('deactivate', User::factory()->withRole(RoleEnum::INVESTIGATOR)->create()));
        $this->assertTrue($admin->can('deactivate', User::factory()->withRole(RoleEnum::SUPERVISOR)->create()));
        $this->assertFalse($admin->can('deactivate', User::factory()->withRole(RoleEnum::ADMIN)->create()));
        $this->assertFalse($admin->can('deactivate', User::factory()->withRole(RoleEnum::SUPERADMIN)->create()));
    }

    public function test_superadmin_cannot_deactivate_their_own_account(): void
    {
        $superadmin = User::factory()->withRole(RoleEnum::SUPERADMIN)->create();

        $this->assertFalse($superadmin->can('deactivate', $superadmin));
        $this->assertTrue($superadmin->can('deactivate', User::factory()->withRole(RoleEnum::ADMIN)->create()));
    }

    public function test_admin_can_resend_invitations_only_for_roles_they_could_create(): void
    {
        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create();

        $this->assertTrue($admin->can('resendInvitation', User::factory()->withRole(RoleEnum::INVESTIGATOR)->create()));
        $this->assertFalse($admin->can('resendInvitation', User::factory()->withRole(RoleEnum::ADMIN)->create()));
    }

    public function test_role_checks_work_when_roles_are_eager_loaded(): void
    {
        $admin = User::factory()->withRole(RoleEnum::ADMIN)->create()->load('roles');

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasRole(RoleEnum::ADMIN));
        $this->assertFalse($admin->isSuperAdmin());
    }
}
