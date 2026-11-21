<?php

use App\Enums\ReportActivityTypeEnum;
use App\Enums\ReportStatusEnum;
use App\Models\Report;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Report')] class extends Component {
    public Report $report;

    public ?string $assigneeId = null;

    public string $priority = '2';

    public string $statusReason = '';

    public string $note = '';

    public function mount(Report $report): void
    {
        $this->authorize('view', $report);

        $this->report = $report;
        $this->syncFormState();
    }

    /**
     * Move the case to another status, optionally recording why.
     */
    public function changeStatus(string $status): void
    {
        $target = ReportStatusEnum::tryFrom($status);

        abort_if($target === null, 422);

        $this->authorize('changeStatus', [$this->report, $target]);

        $validated = $this->validate([
            'statusReason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->report->transitionTo($target, Auth::user(), $validated['statusReason'] ?: null);

        $this->statusReason = '';
        $this->refreshReport();

        Flux::toast(variant: 'success', text: __('Status changed to :status.', ['status' => $target->label()]));
    }

    public function assign(): void
    {
        $this->authorize('assign', $this->report);

        $validated = $this->validate([
            'assigneeId' => [
                'nullable',
                Rule::in($this->assignableStaff->pluck('id')->map(fn (int $id): string => (string) $id)->all()),
            ],
        ], [
            'assigneeId.in' => __('Choose an active investigator or supervisor.'),
        ]);

        $assignee = filled($validated['assigneeId']) ? User::find($validated['assigneeId']) : null;

        $this->report->assignTo($assignee, Auth::user());
        $this->refreshReport();

        Flux::toast(
            variant: 'success',
            text: $assignee ? __('Case assigned to :name.', ['name' => $assignee->full_name]) : __('Case unassigned.'),
        );
    }

    public function updatePriority(): void
    {
        $this->authorize('changePriority', $this->report);

        $validated = $this->validate([
            'priority' => ['required', Rule::in(array_map('strval', Report::PRIORITIES))],
        ]);

        $this->report->changePriority((int) $validated['priority'], Auth::user());
        $this->refreshReport();

        Flux::toast(variant: 'success', text: __('Priority updated.'));
    }

    public function addNote(): void
    {
        $this->authorize('addNote', $this->report);

        $validated = $this->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $this->report->addNote($validated['note'], Auth::user());

        $this->note = '';
        $this->refreshReport();

        Flux::toast(variant: 'success', text: __('Note added.'));
    }

    /**
     * Statuses the current user may move this case to.
     *
     * @return array<int, ReportStatusEnum>
     */
    #[Computed]
    public function availableTransitions(): array
    {
        return array_values(array_filter(
            $this->report->status->allowedTransitions(),
            fn (ReportStatusEnum $target): bool => Auth::user()->can('changeStatus', [$this->report, $target]),
        ));
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function assignableStaff(): Collection
    {
        return User::query()
            ->assignableToCases()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return Collection<int, App\Models\ReportActivity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return $this->report->activities()->with('user')->get();
    }

    /**
     * Human-readable summary for each log entry, keyed by activity id.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function activitySummaries(): array
    {
        $staffIds = $this->activities
            ->where('type', ReportActivityTypeEnum::ASSIGNED)
            ->pluck('to_value')
            ->filter()
            ->unique();

        $staffNames = User::whereIn('id', $staffIds)->get()
            ->mapWithKeys(fn (User $user) => [$user->id => $user->full_name]);

        return $this->activities->mapWithKeys(fn ($activity) => [
            $activity->id => match ($activity->type) {
                ReportActivityTypeEnum::STATUS_CHANGED => __(':from → :to', [
                    'from' => ReportStatusEnum::tryFrom((string) $activity->from_value)?->label() ?? '—',
                    'to' => ReportStatusEnum::tryFrom((string) $activity->to_value)?->label() ?? '—',
                ]),
                ReportActivityTypeEnum::ASSIGNED => $activity->to_value
                    ? __('Assigned to :name', ['name' => $staffNames[(int) $activity->to_value] ?? __('former staff member')])
                    : __('Unassigned'),
                ReportActivityTypeEnum::PRIORITY_CHANGED => __(':from → :to', [
                    'from' => Report::labelForPriority((int) $activity->from_value),
                    'to' => Report::labelForPriority((int) $activity->to_value),
                ]),
                ReportActivityTypeEnum::NOTE_ADDED => '',
            },
        ])->all();
    }

    private function refreshReport(): void
    {
        $this->report->refresh();
        $this->syncFormState();

        unset($this->availableTransitions, $this->activities, $this->activitySummaries);
    }

    private function syncFormState(): void
    {
        $this->assigneeId = $this->report->assigned_to ? (string) $this->report->assigned_to : null;
        $this->priority = (string) $this->report->priority;
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <flux:link :href="route('staff.reports.index')" wire:navigate class="text-sm">
                ← {{ __('Back to reports') }}
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-2">{{ $report->subject }}</flux:heading>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm text-zinc-500">{{ $report->tracking_token }}</span>
                <flux:badge size="sm" :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
                <flux:badge size="sm" :color="$report->priorityColor()">{{ __(':priority priority', ['priority' => $report->priorityLabel()]) }}</flux:badge>
                <flux:badge size="sm">{{ $report->category->label() }}</flux:badge>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ── Report content & activity ─────────────────────────────── --}}
        <div class="flex flex-col gap-6 lg:col-span-2">
            <flux:card>
                <flux:heading size="lg">{{ __('Report details') }}</flux:heading>

                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-zinc-500">{{ __('Received') }}</dt>
                        <dd>{{ $report->created_at->format('j M Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('Incident date') }}</dt>
                        <dd>{{ $report->incident_date ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('Location') }}</dt>
                        <dd>{{ $report->incident_location ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('Organisation involved') }}</dt>
                        <dd>{{ $report->organisation_involved ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-zinc-500">{{ __('Reporter contact (optional)') }}</dt>
                        <dd>{{ $report->contact_hint ?: __('None provided — reporter is fully anonymous.') }}</dd>
                    </div>
                </dl>

                <flux:separator class="my-4" />

                <div class="whitespace-pre-line text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">{{ $report->body }}</div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">{{ __('Case log') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Internal only — never shown to the reporter.') }}</flux:text>

                @can('addNote', $report)
                    <form wire:submit="addNote" class="mt-4 flex flex-col gap-3">
                        <flux:textarea wire:model="note" :label="__('Add a note')" rows="3" :placeholder="__('Findings, interview summaries, next steps…')" />
                        <div>
                            <flux:button type="submit" variant="primary" size="sm">{{ __('Add note') }}</flux:button>
                        </div>
                    </form>
                @endcan

                <ol class="mt-6 flex flex-col gap-4">
                    @forelse ($this->activities as $activity)
                        <li wire:key="activity-{{ $activity->id }}" class="flex gap-3">
                            <flux:icon :name="$activity->type->icon()" variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                            <div class="min-w-0 flex-1">
                                <div class="text-sm">
                                    <span class="font-medium">{{ $activity->user?->full_name ?? __('Former staff member') }}</span>
                                    <span class="text-zinc-500">· {{ $activity->type->label() }}</span>
                                    @if ($this->activitySummaries[$activity->id] ?? '')
                                        <span class="text-zinc-500">· {{ $this->activitySummaries[$activity->id] }}</span>
                                    @endif
                                </div>
                                @if ($activity->body)
                                    <p class="mt-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $activity->body }}</p>
                                @endif
                                <time class="text-xs text-zinc-500" datetime="{{ $activity->created_at->toIso8601String() }}">
                                    {{ $activity->created_at->format('j M Y, H:i') }}
                                </time>
                            </div>
                        </li>
                    @empty
                        <flux:text>{{ __('No activity yet.') }}</flux:text>
                    @endforelse
                </ol>
            </flux:card>
        </div>

        {{-- ── Case management sidebar ───────────────────────────────── --}}
        <div class="flex flex-col gap-6">
            <flux:card>
                <flux:heading size="lg">{{ __('Status') }}</flux:heading>

                @if (count($this->availableTransitions) > 0)
                    <div class="mt-4 flex flex-col gap-3">
                        <flux:textarea wire:model="statusReason" :label="__('Reason (optional)')" rows="2" />
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->availableTransitions as $transition)
                                <flux:button
                                    wire:key="transition-{{ $transition->value }}"
                                    wire:click="changeStatus('{{ $transition->value }}')"
                                    size="sm"
                                    :variant="$transition === App\Enums\ReportStatusEnum::CLOSED ? 'danger' : 'primary'"
                                >
                                    {{ __('Mark :status', ['status' => $transition->label()]) }}
                                </flux:button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <flux:text class="mt-2">{{ __('No status changes available to you.') }}</flux:text>
                @endif
            </flux:card>

            <flux:card>
                <flux:heading size="lg">{{ __('Assignment') }}</flux:heading>

                @can('assign', $report)
                    <form wire:submit="assign" class="mt-4 flex flex-col gap-3">
                        <flux:select wire:model="assigneeId" :label="__('Assigned to')">
                            <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                            @foreach ($this->assignableStaff as $staff)
                                <flux:select.option wire:key="staff-{{ $staff->id }}" :value="$staff->id">
                                    {{ $staff->full_name }} ({{ $staff->roleLabels() }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <div>
                            <flux:button type="submit" size="sm">{{ __('Save assignment') }}</flux:button>
                        </div>
                    </form>
                @else
                    <flux:text class="mt-2">{{ $report->assignee?->full_name ?? __('Unassigned') }}</flux:text>
                @endcan
            </flux:card>

            <flux:card>
                <flux:heading size="lg">{{ __('Priority') }}</flux:heading>

                @can('changePriority', $report)
                    <form wire:submit="updatePriority" class="mt-4 flex flex-col gap-3">
                        <flux:select wire:model="priority">
                            @foreach (App\Models\Report::PRIORITIES as $priorityOption)
                                <flux:select.option wire:key="priority-{{ $priorityOption }}" :value="$priorityOption">
                                    {{ App\Models\Report::labelForPriority($priorityOption) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <div>
                            <flux:button type="submit" size="sm">{{ __('Save priority') }}</flux:button>
                        </div>
                    </form>
                @else
                    <flux:text class="mt-2">{{ $report->priorityLabel() }}</flux:text>
                @endcan
            </flux:card>
        </div>
    </div>
</div>
