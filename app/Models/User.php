<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['first_name', 'last_name', 'email', 'department_id', 'supervisor_id', 'password', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The numeric id stays the primary key; the uuid column is filled automatically
     * and used in URLs instead, so profile links cannot be guessed or enumerated.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Fully random (version 4) so the identifier does not reveal when the account was created.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    // ── Relationship ──────────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * The department a supervisor or investigator works in.
     * Administrators have no department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The supervisor whose team this investigator belongs to.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Investigators in this supervisor's team.
     */
    public function team(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Changes made to this account by administrators, newest first.
     */
    public function staffActivities(): HasMany
    {
        return $this->hasMany(StaffActivity::class)->latest()->latest('id');
    }

    /**
     * The single role a staff account holds (accounts are created with one role).
     */
    public function primaryRole(): ?RoleEnum
    {
        return $this->roles->first()?->name;
    }

    // ── Core role checks ──────────────────────────────────────────────────

    /**
     * Check if the user has a given role (or any of an array of roles).
     * Uses the in-memory collection when roles are already eager-loaded,
     * falling back to a database query when they are not.
     */
    public function hasRole(RoleEnum|string|array $role): bool
    {
        return $this->hasAnyRole(is_array($role) ? $role : [$role]);
    }

    /**
     * Check whether the user holds any of the given roles.
     * Accepts a variadic list or an array of role names or RoleEnum cases.
     *
     * @param  array<int, RoleEnum|string>|RoleEnum|string  $roles
     */
    public function hasAnyRole(array|RoleEnum|string $roles): bool
    {
        $roleNames = array_map(
            fn (RoleEnum|string $role): string => $role instanceof RoleEnum ? $role->value : $role,
            is_array($roles) ? $roles : func_get_args(),
        );

        if ($this->relationLoaded('roles')) {
            // Role::name is cast to RoleEnum, so compare on the backing value.
            return $this->roles->contains(
                fn (Role $role): bool => in_array($role->name->value, $roleNames, strict: true)
            );
        }

        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    // ── Convenience shortcuts ─────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPERADMIN->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([RoleEnum::SUPERADMIN->value, RoleEnum::ADMIN->value]);
    }

    public function isSupervisor(): bool
    {
        return $this->hasAnyRole([RoleEnum::SUPERADMIN->value, RoleEnum::ADMIN->value, RoleEnum::SUPERVISOR->value]);
    }

    public function isInvestigator(): bool
    {
        return $this->hasRole(RoleEnum::INVESTIGATOR->value);
    }

    /**
     * Roles this user is allowed to give to a new staff account.
     * Superadmins may create any role; admins may create supervisors
     * and investigators only.
     *
     * @return array<int, RoleEnum>
     */
    public function creatableRoles(): array
    {
        if ($this->isSuperAdmin()) {
            return RoleEnum::cases();
        }

        if ($this->hasRole(RoleEnum::ADMIN)) {
            return [RoleEnum::SUPERVISOR, RoleEnum::INVESTIGATOR];
        }

        return [];
    }

    public function canCreateRole(RoleEnum $role): bool
    {
        return in_array($role, $this->creatableRoles(), strict: true);
    }

    // ── Role assignment ───────────────────────────────────────────────────

    /**
     * Assign a role to the user.
     * Silently skips if the user already holds the role.
     */
    public function assignRole(string $roleName): self
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        if (! $this->hasRole($roleName)) {
            $this->roles()->attach($role);
            $this->unsetRelation('roles'); // clear cached relation
        }

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleName): self
    {
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $this->roles()->detach($role);
            $this->unsetRelation('roles');
        }

        return $this;
    }

    /**
     * Replace all current roles with the given set.
     */
    public function syncRoles(array $roleNames): self
    {
        $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
        $this->roles()->sync($roleIds);
        $this->unsetRelation('roles');

        return $this;
    }

    // ── Display helpers ───────────────────────────────────────────────────

    /**
     * Comma-separated list of role labels for display.
     * e.g. "Investigator, Supervisor"
     */
    public function roleLabels(): string
    {
        return $this->roles->pluck('label')->join(', ');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Users holding the given role.
     */
    public function scopeWithRole($query, RoleEnum $role)
    {
        return $query->whereHas('roles', fn ($roles) => $roles->where('name', $role->value));
    }

    /**
     * Active staff who can be handed a case: investigators and supervisors.
     */
    public function scopeAssignableToCases($query)
    {
        return $query->active()->whereHas('roles', fn ($roles) => $roles->whereIn('name', [
            RoleEnum::INVESTIGATOR->value,
            RoleEnum::SUPERVISOR->value,
        ]));
    }

    /**
     * Leave out staff who work in the given department, so a case about a
     * department is not handed to someone from it. Staff with no department
     * are kept. A null department applies no filter.
     */
    public function scopeOutsideDepartment($query, ?int $departmentId)
    {
        return $query->when($departmentId !== null, fn ($query) => $query->where(
            fn ($query) => $query->whereNull('department_id')->orWhere('department_id', '!=', $departmentId)
        ));
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'assigned_to');
    }

    // ── State helpers ─────────────────────────────────────────────────────

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name.' '.$this->last_name,
        );
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->full_name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
