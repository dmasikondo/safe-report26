<?php

use App\Enums\ReportCategoryEnum;
use App\Enums\ReportStatusEnum;
use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Reports')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'open';

    #[Url]
    public string $category = '';

    #[Url]
    public string $priority = '';

    /** all | mine | unassigned (supervisors only) */
    #[Url]
    public string $assignment = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Report::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'category', 'priority', 'assignment'], strict: true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function reports(): LengthAwarePaginator
    {
        $user = Auth::user();
        $search = trim($this->search);

        return Report::query()
            ->visibleTo($user)
            ->with('assignee')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('tracking_token', strtoupper($search))
                ->orWhere('subject', 'like', "%{$search}%")))
            ->when($this->status === 'open', fn ($query) => $query->open())
            ->when($this->status === 'finished', fn ($query) => $query->closed())
            ->when(ReportStatusEnum::tryFrom($this->status), fn ($query, $status) => $query->where('status', $status->value))
            ->when(ReportCategoryEnum::tryFrom($this->category), fn ($query, $category) => $query->forCategory($category))
            ->when(in_array((int) $this->priority, Report::PRIORITIES, strict: true), fn ($query) => $query->where('priority', (int) $this->priority))
            ->when($user->isSupervisor() && $this->assignment === 'mine', fn ($query) => $query->where('assigned_to', $user->id))
            ->when($user->isSupervisor() && $this->assignment === 'unassigned', fn ($query) => $query->unassigned())
            ->orderByDesc('priority')
            ->latest()
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">
            {{ auth()->user()->isSupervisor() ? __('All reports') : __('My cases') }}
        </flux:heading>
        <flux:text class="mt-1">
            {{ auth()->user()->isSupervisor()
                ? __('Every report submitted to the platform. Assign cases to investigators from the report page.')
                : __('Reports assigned to you for investigation.') }}
        </flux:text>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Subject or tracking token')" class="lg:col-span-2" />

        <flux:select wire:model.live="status">
            <flux:select.option value="open">{{ __('Open cases') }}</flux:select.option>
            <flux:select.option value="finished">{{ __('Resolved or closed') }}</flux:select.option>
            <flux:select.option value="">{{ __('Any status') }}</flux:select.option>
            @foreach (App\Enums\ReportStatusEnum::cases() as $statusOption)
                <flux:select.option wire:key="status-{{ $statusOption->value }}" :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="category">
            <flux:select.option value="">{{ __('Any category') }}</flux:select.option>
            @foreach (App\Enums\ReportCategoryEnum::cases() as $categoryOption)
                <flux:select.option wire:key="category-{{ $categoryOption->value }}" :value="$categoryOption->value">{{ $categoryOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-3">
            <flux:select wire:model.live="priority">
                <flux:select.option value="">{{ __('Any priority') }}</flux:select.option>
                @foreach (App\Models\Report::PRIORITIES as $priorityOption)
                    <flux:select.option wire:key="priority-{{ $priorityOption }}" :value="$priorityOption">{{ App\Models\Report::labelForPriority($priorityOption) }}</flux:select.option>
                @endforeach
            </flux:select>

            @if (auth()->user()->isSupervisor())
                <flux:select wire:model.live="assignment">
                    <flux:select.option value="all">{{ __('Everyone') }}</flux:select.option>
                    <flux:select.option value="mine">{{ __('Mine') }}</flux:select.option>
                    <flux:select.option value="unassigned">{{ __('Unassigned') }}</flux:select.option>
                </flux:select>
            @endif
        </div>
    </div>

    <flux:table :paginate="$this->reports">
        <flux:table.columns>
            <flux:table.column>{{ __('Report') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Priority') }}</flux:table.column>
            <flux:table.column>{{ __('Assigned to') }}</flux:table.column>
            <flux:table.column>{{ __('Received') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->reports as $report)
                <flux:table.row wire:key="report-{{ $report->id }}">
                    <flux:table.cell class="max-w-xs">
                        <flux:link :href="route('staff.reports.show', $report)" wire:navigate class="block truncate font-medium">
                            {{ $report->subject }}
                        </flux:link>
                        <span class="font-mono text-xs text-zinc-500">{{ $report->tracking_token }}</span>
                    </flux:table.cell>
                    <flux:table.cell>{{ $report->category->label() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$report->priorityColor()">{{ $report->priorityLabel() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $report->assignee?->full_name ?? __('Unassigned') }}</flux:table.cell>
                    <flux:table.cell>{{ $report->created_at->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">
                        {{ __('No reports match these filters.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
