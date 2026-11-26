<?php

use App\Actions\Staff\CreateStaffMember;
use App\Concerns\ProfileValidationRules;
use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add staff member')] class extends Component {
    use ProfileValidationRules;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $role = '';

    public string $department_id = '';

    public function mount(): void
    {
        $this->authorize('create', User::class);

        $this->role = RoleEnum::INVESTIGATOR->value;
    }

    /**
     * Roles the signed-in user may give to a new account.
     *
     * @return array<int, RoleEnum>
     */
    #[Computed]
    public function creatableRoles(): array
    {
        return Auth::user()->creatableRoles();
    }

    /**
     * @return Collection<int, Department>
     */
    #[Computed]
    public function departments(): Collection
    {
        return Department::ordered()->get(['id', 'name']);
    }

    /**
     * Whether the chosen role needs a department (supervisors and investigators).
     */
    #[Computed]
    public function roleRequiresDepartment(): bool
    {
        return RoleEnum::tryFrom($this->role)?->requiresDepartment() ?? false;
    }

    public function save(CreateStaffMember $createStaffMember): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            ...$this->profileRules(),
            'role' => ['required', Rule::enum(RoleEnum::class)->only($this->creatableRoles)],
            'department_id' => [
                Rule::requiredIf(fn (): bool => $this->roleRequiresDepartment),
                'nullable',
                'integer',
                Rule::exists('departments', 'id'),
            ],
        ], [
            'role.enum' => __('You are not allowed to create accounts with this role.'),
            'department_id.required' => __('Choose the department this staff member works in.'),
            'department_id.exists' => __('Choose a department from the list.'),
        ]);

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
        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="first_name" :label="__('First name')" required autofocus />
            <flux:input wire:model="last_name" :label="__('Last name')" required />
        </div>

        <flux:input wire:model="email" :label="__('Email address')" type="email" required />

        <flux:radio.group wire:model.live="role" :label="__('Role')">
            @foreach ($this->creatableRoles as $roleOption)
                <flux:radio
                    wire:key="role-{{ $roleOption->value }}"
                    :value="$roleOption->value"
                    :label="$roleOption->label()"
                    :description="$roleOption->description()"
                />
            @endforeach
        </flux:radio.group>

        @if ($this->roleRequiresDepartment)
            <flux:field>
                <flux:label>{{ __('Department') }}</flux:label>
                <flux:select wire:model="department_id" :placeholder="__('Choose a department')" required>
                    @foreach ($this->departments as $department)
                        <flux:select.option wire:key="department-{{ $department->id }}" :value="$department->id">{{ $department->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>{{ __('Used to avoid assigning this person cases about their own department.') }}</flux:description>
                <flux:error name="department_id" />
            </flux:field>
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">{{ __('Create account') }}</flux:button>
            <flux:button :href="route('staff.users.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
