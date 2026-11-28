<?php

namespace App\Traits;

use App\Actions\Staff\CreateStaffMember;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Account actions shared by the Livewire pages that show <x-staff.member-actions>.
 * Accounts are looked up by uuid so the numeric user ids never appear in the page.
 */
trait ManagesStaffAccounts
{
    public function toggleActive(string $userUuid): void
    {
        $user = $this->findStaffAccount($userUuid);

        $this->authorize('deactivate', $user);

        $user->is_active ? $user->deactivate() : $user->activate();

        Flux::toast(
            variant: 'success',
            text: $user->is_active
                ? __(':name can sign in again.', ['name' => $user->full_name])
                : __(':name has been deactivated and signed out.', ['name' => $user->full_name]),
        );
    }

    public function resendInvitation(string $userUuid, CreateStaffMember $createStaffMember): void
    {
        $user = $this->findStaffAccount($userUuid);

        $this->authorize('resendInvitation', $user);

        $createStaffMember->sendInvitation($user);

        Flux::toast(variant: 'success', text: __('Set-password link sent to :email.', ['email' => $user->email]));
    }

    /**
     * A malformed value is treated as not found rather than sent to the uuid column,
     * which the database would reject.
     */
    private function findStaffAccount(string $userUuid): User
    {
        if (! Str::isUuid($userUuid)) {
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        return User::with('roles')->where('uuid', $userUuid)->firstOrFail();
    }
}
