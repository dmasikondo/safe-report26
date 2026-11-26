<?php

use App\Actions\Staff\CreateStaffMember;
use App\Enums\RoleEnum;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Staff')] class extends Component {
    use WithPagination;

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

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('deactivate', $user);

        $user->is_active ? $user->deactivate() : $user->activate();

        Flux::toast(
            variant: 'success',
            text: $user->is_active
                ? __(':name can sign in again.', ['name' => $user->full_name])
                : __(':name has been deactivated and signed out.', ['name' => $user->full_name]),
        );
    }

    public function resendInvitation(int $userId, CreateStaffMember $createStaffMember): void
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->authorize('resendInvitation', $user);

        $createStaffMember->sendInvitation($user);

        Flux::toast(variant: 'success', text: __('Set-password link sent to :email.', ['email' => $user->email]));
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
                        <div class="font-medium">{{ $member->full_name }}</div>
                        <div class="text-xs text-zinc-500">{{ $member->email }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $member->roleLabels() ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $member->department?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($member->isInvestigator())
                            @if ($member->supervisor)
                                {{ $member->supervisor->full_name }}
                            @else
                                <span class="text-zinc-400">{{ __('Unassigned') }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$member->is_active ? 'green' : 'zinc'">
                            {{ $member->is_active ? __('Active') : __('Deactivated') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $member->last_login_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            @can('resendInvitation', $member)
                                <flux:button size="xs" variant="ghost" wire:click="resendInvitation({{ $member->id }})">
                                    {{ __('Resend invite') }}
                                </flux:button>
                            @endcan
                            @can('deactivate', $member)
                                <flux:button
                                    size="xs"
                                    :variant="$member->is_active ? 'danger' : 'filled'"
                                    wire:click="toggleActive({{ $member->id }})"
                                    wire:confirm="{{ $member->is_active ? __('Deactivate this account? They will be signed out immediately.') : __('Reactivate this account?') }}"
                                >
                                    {{ $member->is_active ? __('Deactivate') : __('Activate') }}
                                </flux:button>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No staff match these filters.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
