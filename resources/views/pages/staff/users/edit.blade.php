<?php

use App\Actions\Staff\UpdateStaffMember;
use App\Concerns\StaffAccountForm;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit staff member')] class extends Component {
    use StaffAccountForm;

    public User $user;

    /** Set when the editor has seen the department conflict warning and saves anyway. */
    public bool $confirmDepartmentConflicts = false;

    public function mount(User $user): void
    {
        $this->authorize('editStaffAccount', $user);

        $this->user = $user->load(['roles', 'department', 'supervisor']);

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->role = $user->primaryRole()?->value ?? '';
        $this->department_id = (string) ($user->department_id ?? '');
        $this->supervisor_id = (string) ($user->supervisor_id ?? '');
    }

    public function updatedDepartmentId(): void
    {
        $this->confirmDepartmentConflicts = false;
    }

    /**
     * Roles offered on the form: those the editor may create, plus the
     * account's current role so it always shows.
     *
     * @return array<int, RoleEnum>
     */
    #[Computed]
    public function roleOptions(): array
    {
        $roles = $this->creatableRoles;
        $currentRole = $this->user->primaryRole();

        if ($currentRole !== null && ! in_array($currentRole, $roles, strict: true)) {
            array_unshift($roles, $currentRole);
        }

        return $roles;
    }

    /**
     * Why the role cannot be changed at all, or null when it can.
     */
    #[Computed]
    public function roleLockReason(): ?string
    {
        $otherRoles = array_filter(
            $this->roleOptions,
            fn (RoleEnum $role): bool => $role !== $this->user->primaryRole(),
        );

        foreach ($otherRoles as $role) {
            if (Auth::user()->can('changeRole', [$this->user, $role])) {
                return null;
            }
        }

        return $this->user->primaryRole() === RoleEnum::SUPERADMIN
            ? __('This is the only active superadmin, so the role cannot be changed.')
            : __('You are not allowed to change this account\'s role.');
    }

    /**
     * Open cases this person holds that come from the department they are
     * being moved into, which would be a conflict of interest.
     *
     * @return Collection<int, Report>
     */
    #[Computed]
    public function conflictingCases(): Collection
    {
        $departmentId = (int) $this->department_id;

        if (! $this->roleRequiresDepartment || $departmentId === 0 || $departmentId === $this->user->department_id) {
            return new Collection;
        }

        return $this->user->assignedReports()
            ->open()
            ->where('department_id', $departmentId)
            ->orderByDesc('priority')
            ->get();
    }

    protected function accountBeingEditedId(): ?int
    {
        return $this->user->id;
    }

    public function save(UpdateStaffMember $updateStaffMember): void
    {
        $this->authorize('editStaffAccount', $this->user);

        $validated = $this->validate(
            $this->staffAccountRules($this->roleOptions),
            $this->staffAccountMessages(),
        );

        $role = RoleEnum::from($validated['role']);

        if ($role !== $this->user->primaryRole()) {
            $this->authorize('changeRole', [$this->user, $role]);

            if ($blocker = $updateStaffMember->roleChangeBlocker($this->user, $role)) {
                $this->addError('role', $blocker);

                return;
            }
        }

        if ($this->conflictingCases->isNotEmpty() && ! $this->confirmDepartmentConflicts) {
            $this->confirmDepartmentConflicts = true;
            $this->addError('department_id', __('Check the warning below, then save again to confirm.'));

            return;
        }

        $emailChanged = $validated['email'] !== $this->user->email;

        $user = $updateStaffMember->handle($this->user, $validated, $role, Auth::user());

        Flux::toast(
            variant: 'success',
            text: $emailChanged
                ? __(':name was updated and signed out. A set-password link has been emailed to :email.', [
                    'name' => $user->full_name,
                    'email' => $user->email,
                ])
                : __(':name was updated.', ['name' => $user->full_name]),
        );

        $this->redirectRoute('staff.users.show', $user, navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:link :href="route('staff.users.show', $user)" wire:navigate class="text-sm">← {{ __('Back to profile') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ __('Edit :name', ['name' => $user->full_name]) }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Every change is recorded in the account history.') }}
        </flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <x-staff.account-fields
            :roles="$this->roleOptions"
            :role="$role"
            :role-locked="$this->roleLockReason"
            :departments="$this->departments"
            :requires-department="$this->roleRequiresDepartment"
            :supervisors="$this->supervisors"
            :team-balance="$this->teamBalance"
        />

        @if ($email !== $user->email)
            <flux:callout icon="envelope" color="amber">
                <flux:callout.heading>{{ __('Changing the email address signs this person out') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Their current password stops working and a link to choose a new one is sent to the new address. The old address is told about the change.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        @if ($this->conflictingCases->isNotEmpty())
            <flux:callout icon="exclamation-triangle" color="red">
                <flux:callout.heading>{{ __('Possible conflict of interest') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ trans_choice('{1} This person holds an open case about the department you chose:|[2,*] This person holds :count open cases about the department you chose:', $this->conflictingCases->count()) }}
                </flux:callout.text>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($this->conflictingCases as $case)
                        <li wire:key="conflict-{{ $case->id }}">
                            <flux:link :href="route('staff.reports.show', $case)" wire:navigate class="font-mono">{{ $case->reference }}</flux:link>
                            — {{ $case->category->label() }}
                        </li>
                    @endforeach
                </ul>
                <flux:callout.text class="mt-2">
                    {{ __('Consider reassigning these cases. You can still save the change.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                {{ $confirmDepartmentConflicts && $this->conflictingCases->isNotEmpty() ? __('Save anyway') : __('Save changes') }}
            </flux:button>
            <flux:button :href="route('staff.users.show', $user)" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
