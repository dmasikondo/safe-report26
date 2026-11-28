<?php

use App\Traits\ManagesStaffAccounts;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Staff profile')] class extends Component {
    use ManagesStaffAccounts, WithPagination;

    public User $user;

    /** open | closed | all */
    #[Url]
    public string $cases = 'open';

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
    }

    public function updatedCases(): void
    {
        $this->resetPage();
    }

    /**
     * The profile owner, refreshed so status changes made on this page show immediately.
     */
    #[Computed]
    public function member(): User
    {
        return $this->user->fresh(['roles', 'department', 'supervisor']);
    }

    /**
     * Case lists are only shown to people who may work cases at all, and only
     * include cases the viewer could open themselves.
     */
    #[Computed]
    public function canSeeCases(): bool
    {
        return Auth::user()->can('viewAny', Report::class);
    }

    /**
     * @return array{open: int, closed: int, high_priority: int}
     */
    #[Computed]
    public function caseCounts(): array
    {
        return [
            'open' => $this->assignedCases()->open()->count(),
            'closed' => $this->assignedCases()->closed()->count(),
            'high_priority' => $this->assignedCases()->open()->highPriority()->count(),
        ];
    }

    #[Computed]
    public function assignedReports(): LengthAwarePaginator
    {
        return $this->assignedCases()
            ->when($this->cases === 'open', fn ($query) => $query->open())
            ->when($this->cases === 'closed', fn ($query) => $query->closed())
            ->with('department')
            ->orderByDesc('priority')
            ->latest()
            ->paginate(10);
    }

    /**
     * Investigators in this supervisor's team, with how many open cases each holds.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function team(): Collection
    {
        return $this->user->team()
            ->withRole(RoleEnum::INVESTIGATOR)
            ->with(['roles', 'department'])
            ->withCount(['assignedReports as open_cases_count' => fn ($query) => $query->open()])
            ->orderByDesc('is_active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Cases assigned to the profile owner that the viewer is allowed to see.
     */
    private function assignedCases(): Builder
    {
        return Report::query()
            ->visibleTo(Auth::user())
            ->where('assigned_to', $this->user->id);
    }

    /**
     * Changes administrators made to this account, shown to people who may
     * update it (administrators, and the person themselves).
     *
     * @return Collection<int, App\Models\StaffActivity>|null
     */
    #[Computed]
    public function accountHistory(): ?Collection
    {
        if (! Auth::user()->can('update', $this->user)) {
            return null;
        }

        return $this->user->staffActivities()->with('actor')->limit(50)->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    {{-- ── Header ───────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1" class="break-words">{{ $this->member->full_name }}</flux:heading>
                <x-staff.status-badge :member="$this->member" />
            </div>
            <flux:text class="mt-1 break-all">{{ $this->member->email }}</flux:text>
        </div>

        <x-staff.member-actions :member="$this->member" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ── Account details ──────────────────────────────────────── --}}
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>

            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                <x-list-card.detail :label="__('Role')">{{ $this->member->roleLabels() ?: '—' }}</x-list-card.detail>
                <x-list-card.detail :label="__('Department')">{{ $this->member->department?->name ?? '—' }}</x-list-card.detail>
                @if ($this->member->isInvestigator())
                    <x-list-card.detail :label="__('Supervisor')"><x-staff.supervisor :member="$this->member" /></x-list-card.detail>
                @endif
                <x-list-card.detail :label="__('Last sign-in')">{{ $this->member->last_login_at?->diffForHumans() ?? __('Never') }}</x-list-card.detail>
                <x-list-card.detail :label="__('Account created')">{{ $this->member->created_at->format('j M Y') }}</x-list-card.detail>
            </dl>
        </flux:card>

        {{-- ── Supervisor's team ────────────────────────────────────── --}}
        @if ($this->member->hasRole(App\Enums\RoleEnum::SUPERVISOR))
            <flux:card class="flex flex-col gap-4 lg:col-span-2">
                <div class="flex items-baseline justify-between gap-3">
                    <flux:heading size="lg">{{ __('Team') }}</flux:heading>
                    <flux:text class="text-sm">
                        {{ trans_choice(':count investigator|:count investigators', $this->team->where('is_active', true)->count(), ['count' => $this->team->where('is_active', true)->count()]) }}
                    </flux:text>
                </div>

                @if ($this->team->isEmpty())
                    <flux:text class="text-sm">{{ __('No investigators in this team yet.') }}</flux:text>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->team as $investigator)
                            <li wire:key="team-{{ $investigator->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <div class="min-w-0">
                                    <x-staff.user-link :user="$investigator" class="text-sm font-medium" />
                                    <div class="text-xs text-zinc-500">{{ $investigator->department?->name ?? '—' }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @unless ($investigator->is_active)
                                        <x-staff.status-badge :member="$investigator" />
                                    @endunless
                                    <flux:badge size="sm" color="zinc">
                                        {{ trans_choice(':count open case|:count open cases', $investigator->open_cases_count, ['count' => $investigator->open_cases_count]) }}
                                    </flux:badge>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </flux:card>
        @endif
    </div>

    {{-- ── Assigned cases ───────────────────────────────────────────── --}}
    @if ($this->canSeeCases)
        <section class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <flux:heading size="lg" level="2">{{ __('Assigned cases') }}</flux:heading>
                    <div class="mt-1 flex flex-wrap gap-x-6 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        <span>{{ trans_choice(':count open|:count open', $this->caseCounts['open'], ['count' => $this->caseCounts['open']]) }}</span>
                        <span>{{ trans_choice(':count high priority|:count high priority', $this->caseCounts['high_priority'], ['count' => $this->caseCounts['high_priority']]) }}</span>
                        <span>{{ trans_choice(':count resolved or closed|:count resolved or closed', $this->caseCounts['closed'], ['count' => $this->caseCounts['closed']]) }}</span>
                    </div>
                </div>

                <flux:select wire:model.live="cases" class="max-w-52">
                    <flux:select.option value="open">{{ __('Open cases') }}</flux:select.option>
                    <flux:select.option value="closed">{{ __('Resolved or closed') }}</flux:select.option>
                    <flux:select.option value="all">{{ __('All cases') }}</flux:select.option>
                </flux:select>
            </div>

            {{-- Small screens: cards --}}
            <div class="flex flex-col gap-3 lg:hidden">
                @forelse ($this->assignedReports as $report)
                    <x-list-card wire:key="case-card-{{ $report->id }}">
                        <x-slot:title>
                            <flux:link :href="route('staff.reports.show', $report)" wire:navigate class="font-mono">{{ $report->reference }}</flux:link>
                        </x-slot:title>
                        <x-slot:badge>
                            <flux:badge size="sm" :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
                        </x-slot:badge>

                        <x-list-card.detail :label="__('Category')">{{ $report->category->label() }}</x-list-card.detail>
                        @if ($report->department)
                            <x-list-card.detail :label="__('Department')">{{ $report->department->name }}</x-list-card.detail>
                        @endif
                        <x-list-card.detail :label="__('Priority')">
                            <flux:badge size="sm" :color="$report->priorityColor()">{{ $report->priorityLabel() }}</flux:badge>
                        </x-list-card.detail>
                        <x-list-card.detail :label="__('Received')">{{ $report->created_at->diffForHumans() }}</x-list-card.detail>
                    </x-list-card>
                @empty
                    <flux:text class="py-8 text-center">{{ __('No cases to show.') }}</flux:text>
                @endforelse

                <flux:pagination :paginator="$this->assignedReports" />
            </div>

            {{-- Large screens: table --}}
            <div class="hidden lg:block">
                <flux:table :paginate="$this->assignedReports">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Reference') }}</flux:table.column>
                        <flux:table.column>{{ __('Category') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Priority') }}</flux:table.column>
                        <flux:table.column>{{ __('Received') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->assignedReports as $report)
                            <flux:table.row wire:key="case-{{ $report->id }}">
                                <flux:table.cell>
                                    <flux:link :href="route('staff.reports.show', $report)" wire:navigate class="font-mono">{{ $report->reference }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $report->category->label() }}
                                    @if ($report->department)
                                        <span class="block text-xs text-zinc-500">{{ $report->department->name }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$report->priorityColor()">{{ $report->priorityLabel() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $report->created_at->diffForHumans() }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="py-8 text-center">{{ __('No cases to show.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </section>
    @endif
    {{-- ── Account history ──────────────────────────────────────────── --}}
    @if ($this->accountHistory !== null)
        <flux:card class="flex flex-col gap-4">
            <div>
                <flux:heading size="lg" level="2">{{ __('Account history') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Changes made to this account by administrators.') }}</flux:text>
            </div>

            @if ($this->accountHistory->isEmpty())
                <flux:text>{{ __('No changes have been made since the account was created.') }}</flux:text>
            @else
                <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    @foreach ($this->accountHistory as $change)
                        <li wire:key="account-change-{{ $change->id }}" class="flex flex-col gap-1 py-3 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                            <div class="min-w-0">
                                <span class="font-medium">{{ $change->fieldLabel() }}</span>
                                <span class="break-words text-zinc-600 dark:text-zinc-400">
                                    {{ $change->from_value ?? __('none') }} → {{ $change->to_value ?? __('none') }}
                                </span>
                            </div>
                            <div class="shrink-0 text-xs text-zinc-500">
                                {{ $change->actor?->full_name ?? __('Removed account') }} · <time datetime="{{ $change->created_at->toIso8601String() }}" title="{{ $change->created_at->format('j M Y, H:i') }}">{{ $change->created_at->diffForHumans() }}</time>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    @endif
</div>
