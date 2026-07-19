<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Superadmins bypass every policy check automatically.
     * Returning true here short-circuits all methods below for superadmins.
     */
    public function before(User $authUser, string $ability): bool|null
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        return null; // defer to individual methods
    }

    /**
     * Any admin can view the full user list.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Admins can view any user profile.
     * All users can view their own profile.
     */
    public function view(User $authUser, User $targetUser): bool
    {
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        return $authUser->isAdmin();
    }

    /**
     * Only admins can create new staff accounts.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Admins can update users, but not other admins.
     * Users can update their own profile.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // A plain admin cannot modify another admin or superadmin
        if ($authUser->hasRole('admin') && $targetUser->isAdmin()) {
            return false;
        }

        return $authUser->isAdmin();
    }

    /**
     * Only admins can deactivate accounts.
     * Users cannot deactivate their own account.
     */
    public function deactivate(User $authUser, User $targetUser): bool
    {
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        if ($authUser->hasRole('admin') && $targetUser->isAdmin()) {
            return false;
        }

        return $authUser->isAdmin();
    }

    /**
     * Only admins can delete users.
     * Users cannot delete their own account.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        return $authUser->isAdmin();
    }

    /**
     * Only superadmin can assign or change roles.
     * The before() method already returns true for superadmin,
     * so this effectively returns false for everyone else.
     */
    public function assignRole(User $authUser): bool
    {
        return false;
    }
}