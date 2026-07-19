<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'is_active' => 'boolean'
        ];
    }
    // ── Relationship ──────────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    // ── Core role checks ──────────────────────────────────────────────────

    /**
     * Check if the user has a given role (or any of an array of roles).
     * Uses the in-memory collection when roles are already eager-loaded,
     * falling back to a database query when they are not.
     */
    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];

        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check whether the user holds any of the given roles.
     * Accepts a variadic list or an array.
     *
     * @param array|string $roles
     */
    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();

        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    // ── Convenience shortcuts ─────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPERADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([Role::SUPERADMIN, Role::ADMIN]);
    }

    public function isSupervisor(): bool
    {
        return $this->hasAnyRole([Role::SUPERADMIN, Role::ADMIN, Role::SUPERVISOR]);
    }

    public function isInvestigator(): bool
    {
        return $this->hasRole(Role::INVESTIGATOR);
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
            get: fn() => $this->first_name . ' ' . $this->last_name,
        );
    }
    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->fullname)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
