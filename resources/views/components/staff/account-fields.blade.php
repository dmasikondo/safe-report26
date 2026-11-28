{{--
    <x-staff.account-fields
        :roles="$this->creatableRoles"
        :role="$role"
        :departments="$this->departments"
        :requires-department="$this->roleRequiresDepartment"
        :supervisors="$this->supervisors"
        :team-balance="$this->teamBalance"
    />

    Name, email, role, department and supervisor fields for the add and edit
    staff pages. The inputs bind to the properties of App\Concerns\StaffAccountForm.
    Pass role-locked with a reason to show the role but stop it being changed.
--}}
@props([
    'roles',
    'role',
    'departments',
    'requiresDepartment',
    'supervisors',
    'teamBalance',
    'roleLocked' => null,
])

<div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="first_name" :label="__('First name')" required autofocus />
    <flux:input wire:model="last_name" :label="__('Last name')" required />
</div>

<flux:input wire:model.blur="email" :label="__('Email address')" type="email" required />

<flux:radio.group wire:model.live="role" :label="__('Role')">
    @foreach ($roles as $roleOption)
        <flux:radio
            wire:key="role-{{ $roleOption->value }}"
            :value="$roleOption->value"
            :label="$roleOption->label()"
            :description="$roleOption->description()"
            :disabled="filled($roleLocked)"
        />
    @endforeach
</flux:radio.group>

@if (filled($roleLocked))
    <flux:text class="-mt-4 text-sm">{{ $roleLocked }}</flux:text>
@endif

@if ($requiresDepartment)
    <flux:field>
        <flux:label>{{ __('Department') }}</flux:label>
        <flux:select wire:model.live="department_id" :placeholder="__('Choose a department')" required>
            @foreach ($departments as $department)
                <flux:select.option wire:key="department-{{ $department->id }}" :value="$department->id">{{ $department->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:description>{{ __('Used to avoid assigning this person cases about their own department.') }}</flux:description>
        <flux:error name="department_id" />
    </flux:field>
@endif

@if ($role === App\Enums\RoleEnum::INVESTIGATOR->value && $supervisors->isNotEmpty())
    <flux:field>
        <flux:label badge="{{ __('Optional') }}">{{ __('Supervisor') }}</flux:label>
        <flux:select wire:model="supervisor_id">
            <flux:select.option value="">{{ __('No supervisor yet') }}</flux:select.option>
            @foreach ($supervisors as $supervisor)
                <flux:select.option wire:key="supervisor-{{ $supervisor->id }}" :value="$supervisor->id">{{ $teamBalance->optionLabel($supervisor) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:description>{{ __('Fair share is :fair investigators each. You can also change this later on the Teams page.', ['fair' => $teamBalance->fairShareLabel()]) }}</flux:description>
        <flux:error name="supervisor_id" />
    </flux:field>
@endif
