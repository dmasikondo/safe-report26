<?php

namespace App\Actions\Staff;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateStaffMember
{
    /**
     * Create a backend staff account with a single role and email the new
     * staff member a link to set their own password.
     *
     * Authorization and validation are the caller's responsibility.
     *
     * @param  array{first_name: string, last_name: string, email: string}  $attributes
     */
    public function handle(array $attributes, RoleEnum $role): User
    {
        $user = DB::transaction(function () use ($attributes, $role) {
            $user = User::create([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'email' => $attributes['email'],
                // Unusable random password until the invitation is accepted.
                'password' => Str::password(64),
                'is_active' => true,
            ]);

            $user->assignRole($role->value);

            return $user;
        });

        $this->sendInvitation($user);

        return $user;
    }

    public function sendInvitation(User $user): void
    {
        $roleLabel = $user->roles()->first()?->label ?? 'Staff';

        $user->notify(new StaffInvitationNotification(Password::createToken($user), $roleLabel));
    }
}
