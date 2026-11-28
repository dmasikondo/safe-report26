<?php

use App\Concerns\ManagesStaffAccounts;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Staff')] class extends Component {
    use ManagesStaffAccounts, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role'], strict: true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function staff(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return User::query()
            ->with(['roles', 'department', 'supervisor'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(RoleEnum::tryFrom($this->role), fn ($query, $role) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $role->value)))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Staff') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Backend accounts for administrators, supervisors and investigators.') }}</flux:text>
        </div>

        @can('create', App\Models\User::class)
            <flux:button :href="route('staff.users.create')" wire:navigate variant="primary" icon="plus">
                {{ __('Add staff member') }}
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Name or email')" class="sm:col-span-2" />
        <flux:select wire:model.live="role">
            <flux:select.option value="">{{ __('Any role') }}</flux:select.option>
            @foreach (App\Enums\RoleEnum::cases() as $roleOption)
                <flux:select.option wire:key="role-{{ $roleOption->value }}" :value="$roleOption->value">{{ $roleOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="flex flex-col gap-3 lg:hidden">
        @forelse ($this->staff as $member)
            <x-staff.member-card :member="$member" wire:key="user-card-{{ $member->id }}" />
        @empty
            <flux:text class="py-8 text-center">{{ __('No staff match these filters.') }}</flux:text>
        @endforelse

        <flux:pagination :paginator="$this->staff" />
    </div>

    <div class="hidden lg:block">
        <flux:table :paginate="$this->staff">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Department') }}</flux:table.column>
                <flux:table.column>{{ __('Supervisor') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Last sign-in') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
    
            <flux:table.rows>
                @forelse ($this->staff as $member)
                    <flux:table.row wire:key="user-{{ $member->id }}">
                        <flux:table.cell>
                            <x-staff.user-link :user="$member" class="font-medium" />
                            <div class="text-xs text-zinc-500">{{ $member->email }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $member->roleLabels() ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $member->department?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell><x-staff.supervisor :member="$member" /></flux:table.cell>
                        <flux:table.cell><x-staff.status-badge :member="$member" /></flux:table.cell>
                        <flux:table.cell>{{ $member->last_login_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                        <flux:table.cell align="end"><x-staff.member-actions :member="$member" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No staff match these filters.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
