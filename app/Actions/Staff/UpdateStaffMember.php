<?php

namespace App\Actions\Staff;

use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\StaffEmailChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class UpdateStaffMember
{
    public function __construct(public CreateStaffMember $createStaffMember) {}

    /**
     * Apply an administrator's changes to a staff account and record each
     * changed field in the account history.
     *
     * Authorization and validation are the caller's responsibility; call
     * roleChangeBlocker() first when the role changes.
     *
     * When the email address changes the account is re-invited: the old
     * address is told, the password is replaced with an unusable one, every
     * session is ended and a set-password link goes to the new address.
     *
     * @param  array{first_name: string, last_name: string, email: string, department_id?: int|string|null, supervisor_id?: int|string|null}  $attributes
     */
    public function handle(User $target, array $attributes, RoleEnum $role, User $actor): User
    {
        $target->loadMissing(['roles', 'department', 'supervisor']);

        $before = $this->snapshot($target);
        $oldEmail = $target->email;

        DB::transaction(function () use ($target, $attributes, $role, $actor, $before): void {
            $target->fill([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'email' => $attributes['email'],
                'department_id' => $role->requiresDepartment() ? (($attributes['department_id'] ?? null) ?: null) : null,
                'supervisor_id' => $role === RoleEnum::INVESTIGATOR ? (($attributes['supervisor_id'] ?? null) ?: null) : null,
            ])->save();

            if ($target->primaryRole() !== $role) {
                $target->syncRoles([$role->value]);
            }

            $target->load(['roles', 'department', 'supervisor']);

            foreach ($this->snapshot($target) as $field => $newValue) {
                if ($newValue !== $before[$field]) {
                    StaffActivity::create([
                        'user_id' => $target->id,
                        'actor_id' => $actor->id,
                        'field' => $field,
                        'from_value' => $before[$field],
                        'to_value' => $newValue,
                    ]);
                }
            }
        });

        if ($target->email !== $oldEmail) {
            $this->reinvite($target, $oldEmail);
        }

        return $target;
    }

    /**
     * Why the account cannot move to the given role yet, or null when it can.
     */
    public function roleChangeBlocker(User $target, RoleEnum $newRole): ?string
    {
        $currentRole = $target->primaryRole();

        if ($currentRole === $newRole) {
            return null;
        }

        if ($currentRole === RoleEnum::SUPERVISOR && $target->team()->exists()) {
            return __('This supervisor still has investigators in their team. Move them to another supervisor on the Teams page first.');
        }

        $canHoldCases = in_array($newRole, [RoleEnum::INVESTIGATOR, RoleEnum::SUPERVISOR], strict: true);

        if (! $canHoldCases && $target->assignedReports()->open()->exists()) {
            return __('This person still holds open cases, which a :role cannot be assigned. Reassign the cases first.', [
                'role' => mb_strtolower($newRole->label()),
            ]);
        }

        return null;
    }

    /**
     * Logged fields as people read them.
     *
     * @return array<string, string|null>
     */
    private function snapshot(User $user): array
    {
        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->primaryRole()?->label(),
            'department' => $user->department instanceof Department ? $user->department->name : null,
            'supervisor' => $user->supervisor?->full_name,
        ];
    }

    private function reinvite(User $user, string $oldEmail): void
    {
        Notification::route('mail', $oldEmail)->notify(new StaffEmailChangedNotification($user->first_name));

        $user->forceFill([
            'password' => Str::password(64),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        $this->createStaffMember->sendInvitation($user, afterEmailChange: true);
    }
}
