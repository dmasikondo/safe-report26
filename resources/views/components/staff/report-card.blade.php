{{--
    <x-staff.report-card :report="$report" />
    Small-screen card for one row of the reports list. Expects assignee and department to be eager loaded.
--}}
@props(['report'])

<x-list-card {{ $attributes }}>
    <x-slot:title>
        <flux:link :href="route('staff.reports.show', $report)" wire:navigate class="line-clamp-3">
            {{ $report->subject }}
        </flux:link>
    </x-slot:title>
    <x-slot:subtitle><span class="font-mono">{{ $report->reference }}</span></x-slot:subtitle>
    <x-slot:badge>
        <flux:badge size="sm" :color="$report->priorityColor()">{{ $report->priorityLabel() }}</flux:badge>
    </x-slot:badge>

    <x-list-card.detail :label="__('Status')">
        <flux:badge size="sm" :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
    </x-list-card.detail>
    <x-list-card.detail :label="__('Category')">{{ $report->category->label() }}</x-list-card.detail>
    <x-list-card.detail :label="__('Department')">{{ $report->department?->name ?? __('Not specified') }}</x-list-card.detail>
    <x-list-card.detail :label="__('Assigned to')">
        <x-staff.user-link :user="$report->assignee" :fallback="__('Unassigned')" />
    </x-list-card.detail>
    <x-list-card.detail :label="__('Received')">{{ $report->created_at->diffForHumans() }}</x-list-card.detail>
</x-list-card>
