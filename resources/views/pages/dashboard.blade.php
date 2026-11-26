<?php

use App\Models\Report;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public bool $autoAssign = true;

    public function mount(): void
    {
        $this->autoAssign = Setting::autoAssignEnabled();
    }

    /**
     * Admins switch automatic case assignment on or off.
     */
    public function updatedAutoAssign(bool $enabled): void
    {
        $this->authorize('configureAutoAssignment', Report::class);

        Setting::put(Setting::AUTO_ASSIGN_CASES, $enabled);

        Flux::toast(
            variant: 'success',
            text: $enabled ? __('Automatic assignment turned on.') : __('Automatic assignment turned off.'),
        );
    }

    /**
     * Latest notifications for the signed-in user, unread first.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    #[Computed]
    public function notifications(): \Illuminate\Support\Collection
    {
        return Auth::user()->notifications()
            ->reorder()
            ->orderByRaw('read_at is not null')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function markAllNotificationsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();

        unset($this->notifications);
    }

    public function openNotification(string $notificationId): void
    {
        $notification = Auth::user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        $this->redirect($notification->data['url'] ?? route('dashboard'), navigate: true);
    }
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

    @can('configureAutoAssignment', App\Models\Report::class)
        <flux:card>
            <flux:switch
                wire:model.live="autoAssign"
                :label="__('Automatic case assignment')"
                :description="__('New reports go to the active investigator outside the report\'s department with the fewest open cases. Sexual harassment reports are never assigned automatically; admins are notified instead.')"
            />
        </flux:card>
    @endcan

    @if ($this->notifications->isNotEmpty())
        <flux:card>
            <div class="mb-2 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Notifications') }}</flux:heading>
                @if ($this->notifications->whereNull('read_at')->isNotEmpty())
                    <flux:button size="sm" variant="ghost" wire:click="markAllNotificationsRead">{{ __('Mark all as read') }}</flux:button>
                @endif
            </div>

            @foreach ($this->notifications as $notification)
                <button
                    type="button"
                    wire:key="notification-{{ $notification->id }}"
                    wire:click="openNotification('{{ $notification->id }}')"
                    class="flex w-full items-start gap-3 border-t border-zinc-100 py-3 text-start first:border-t-0 dark:border-zinc-700"
                >
                    <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-brand-600' => $notification->read_at === null, 'bg-transparent' => $notification->read_at !== null])></span>
                    <span class="min-w-0 flex-1">
                        <span @class(['block text-sm', 'font-medium text-zinc-900 dark:text-zinc-100' => $notification->read_at === null, 'text-zinc-500 dark:text-zinc-400' => $notification->read_at !== null])>
                            {{ $notification->data['message'] ?? '' }}
                        </span>
                        <span class="text-xs text-zinc-500">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @endforeach
        </flux:card>
    @endif

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
