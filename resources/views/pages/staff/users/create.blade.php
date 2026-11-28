<?php

use App\Actions\Staff\CreateStaffMember;
use App\Traits\StaffAccountForm;
use App\Enums\RoleEnum;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add staff member')] class extends Component {
    use StaffAccountForm;

    public function mount(): void
    {
        $this->authorize('create', User::class);

        $this->role = RoleEnum::INVESTIGATOR->value;
    }

    public function save(CreateStaffMember $createStaffMember): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate(
            $this->staffAccountRules($this->creatableRoles),
            [...$this->staffAccountMessages(), 'role.enum' => __('You are not allowed to create accounts with this role.')],
        );

        $role = RoleEnum::from($validated['role']);

        $this->authorize('createWithRole', [User::class, $role]);

        $user = $createStaffMember->handle($validated, $role);

        Flux::toast(
            variant: 'success',
            text: __(':name was added. A set-password link has been emailed to :email.', [
                'name' => $user->full_name,
                'email' => $user->email,
            ]),
        );

        $this->redirectRoute('staff.users.index', navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:link :href="route('staff.users.index')" wire:navigate class="text-sm">← {{ __('Back to staff') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ __('Add staff member') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('The new staff member receives an email with a link to set their own password.') }}
        </flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <x-staff.account-fields
            :roles="$this->creatableRoles"
            :role="$role"
            :departments="$this->departments"
            :requires-department="$this->roleRequiresDepartment"
            :supervisors="$this->supervisors"
            :team-balance="$this->teamBalance"
        />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">{{ __('Create account') }}</flux:button>
            <flux:button :href="route('staff.users.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
