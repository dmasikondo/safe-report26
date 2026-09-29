{{--
    <x-report.tracking-badge :report="$report" />
    Renders the status pill for a report, using the status colour from ReportStatusEnum.
--}}
@props(['report'])

@php
    $color = $report->status->color();
    $label = $report->status->label();

    $colorMap = [
        'zinc'   => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        'blue'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'amber'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
        'green'  => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'red'    => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ];

    $classes = $colorMap[$color] ?? $colorMap['zinc'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ' . $classes]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-70"></span>
    {{ $label }}
</span>
