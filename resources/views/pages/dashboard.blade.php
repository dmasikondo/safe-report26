<?php

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * Headline numbers for the signed-in staff member's caseload.
     *
     * @return array<int, array{label: string, value: int, icon: string}>
     */
    #[Computed]
    public function stats(): array
    {
        $user = Auth::user();

        if (! $user->can('viewAny', Report::class)) {
            return [];
        }

        $visible = fn () => Report::query()->visibleTo($user);

        if ($user->isSupervisor()) {
            return [
                ['label' => __('Open cases'), 'value' => $visible()->open()->count(), 'icon' => 'folder-open'],
                ['label' => __('Unassigned'), 'value' => $visible()->open()->unassigned()->count(), 'icon' => 'inbox'],
                ['label' => __('High priority'), 'value' => $visible()->open()->highPriority()->count(), 'icon' => 'flag'],
            ];
        }

        return [
            ['label' => __('My open cases'), 'value' => $visible()->open()->count(), 'icon' => 'folder-open'],
            ['label' => __('High priority'), 'value' => $visible()->open()->highPriority()->count(), 'icon' => 'flag'],
            ['label' => __('Finished'), 'value' => $visible()->closed()->count(), 'icon' => 'check-circle'],
        ];
    }

    /**
     * Most recent open cases visible to the user.
     *
     * @return Collection<int, Report>
     */
    #[Computed]
    public function recentReports(): Collection
    {
        if (! Auth::user()->can('viewAny', Report::class)) {
            return new Collection;
        }

        return Report::query()
            ->visibleTo(Auth::user())
            ->open()
            ->with('assignee')
            ->latest()
            ->limit(5)
            ->get();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Welcome, :name', ['name' => auth()->user()->first_name]) }}</flux:heading>
        <flux:text class="mt-1">{{ auth()->user()->roleLabels() ?: __('No role assigned') }}</flux:text>
    </div>

    @if (empty($this->stats))
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>
                {{ __('Your account has no case-handling role yet. Ask an administrator to assign one.') }}
            </flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($this->stats as $stat)
                <flux:card wire:key="stat-{{ $loop->index }}" class="flex items-center gap-4">
                    <flux:icon :name="$stat['icon']" class="size-8 text-zinc-400" />
                    <div>
                        <flux:text>{{ $stat['label'] }}</flux:text>
                        <flux:heading size="xl">{{ $stat['value'] }}</flux:heading>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <flux:card>
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent open cases') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('staff.reports.index')" wire:navigate>
                    {{ __('View all') }}
                </flux:button>
            </div>

            @forelse ($this->recentReports as $report)
                <a
                    wire:key="recent-{{ $report->id }}"
                    href="{{ route('staff.reports.show', $report) }}"
                    wire:navigate
                    class="flex items-center justify-between gap-4 border-t border-zinc-100 py-3 first:border-t-0 dark:border-zinc-700"
                >
                    <div class="min-w-0">
                        <flux:heading class="truncate">{{ $report->subject }}</flux:heading>
                        <flux:text size="sm">
                            {{ $report->category->label() }} · {{ $report->created_at->diffForHumans() }}
                            · {{ $report->assignee?->full_name ?? __('Unassigned') }}
                        </flux:text>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <flux:badge size="sm" :color="$report->priorityColor()">{{ $report->priorityLabel() }}</flux:badge>
                        <flux:badge size="sm" :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
                    </div>
                </a>
            @empty
                <flux:text>{{ __('No open cases.') }}</flux:text>
            @endforelse
        </flux:card>
    @endif
</div>
