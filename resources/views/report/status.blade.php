<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Report Status — SafeReport</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

<header class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
            <flux:icon.shield-check class="size-5 text-blue-600" />
            SafeReport
        </a>
        <a href="{{ route('report.track') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
            ← Track another report
        </a>
    </div>
</header>

<main class="mx-auto max-w-2xl px-4 py-10">

    {{-- Header --}}
    <div class="mb-8">
        <div class="mb-1 flex items-center gap-2">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Report status</h1>
            <x-report.tracking-badge :report="$report" class="ml-1" />
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Token:
            <code class="ml-1 rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                {{ $report->tracking_token }}
            </code>
        </p>
    </div>

    {{-- Report meta card --}}
    <div class="mb-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm divide-y divide-zinc-100 dark:divide-zinc-800 overflow-hidden">
        <div class="flex items-center gap-4 px-5 py-3.5">
            <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wider text-zinc-400">Category</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300 font-medium">{{ $report->category->label() }}</span>
        </div>
        <div class="flex items-center gap-4 px-5 py-3.5 bg-zinc-50 dark:bg-zinc-800/50">
            <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wider text-zinc-400">Subject</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $report->subject }}</span>
        </div>
        @if ($report->organisation_involved)
        <div class="flex items-center gap-4 px-5 py-3.5">
            <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wider text-zinc-400">Organisation</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $report->organisation_involved }}</span>
        </div>
        @endif
        <div class="flex items-center gap-4 px-5 py-3.5 bg-zinc-50 dark:bg-zinc-800/50">
            <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wider text-zinc-400">Submitted</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $report->created_at->format('d M Y, H:i') }} UTC</span>
        </div>
        <div class="flex items-center gap-4 px-5 py-3.5">
            <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wider text-zinc-400">Last updated</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $report->updated_at->diffForHumans() }}</span>
        </div>
    </div>

    {{-- Status timeline --}}
    @php
        $timeline   = \App\Enums\ReportStatusEnum::timeline();
        $currentIdx = array_search($report->status, $timeline, true);
    @endphp

    <h2 class="mb-4 text-base font-semibold text-zinc-800 dark:text-zinc-200">Progress</h2>

    <div class="mb-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6">
        <ol class="relative space-y-6 before:absolute before:left-[1.1875rem] before:top-2 before:h-[calc(100%-1rem)] before:w-px before:bg-zinc-200 dark:before:bg-zinc-700">
            @foreach ($timeline as $idx => $status)
                @php
                    $isPast    = $idx < $currentIdx;
                    $isCurrent = $idx === $currentIdx;
                    $isFuture  = $idx > $currentIdx;
                @endphp
                <li class="relative flex items-start gap-4 pl-10">
                    {{-- Step dot --}}
                    <span @class([
                        'absolute left-0 flex size-9.5 items-center justify-center rounded-full ring-4 ring-white dark:ring-zinc-900 transition-colors',
                        'bg-green-500 text-white'   => $isPast,
                        'bg-blue-600 text-white shadow-md shadow-blue-200 dark:shadow-blue-900/40' => $isCurrent,
                        'bg-zinc-200 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500' => $isFuture,
                    ])>
                        @if ($isPast)
                            <flux:icon.check class="size-4" />
                        @elseif ($isCurrent)
                            <span class="size-2.5 rounded-full bg-white"></span>
                        @else
                            <span class="size-2 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                        @endif
                    </span>

                    {{-- Label --}}
                    <div class="pt-1.5">
                        <p @class([
                            'text-sm font-semibold',
                            'text-green-600 dark:text-green-400'  => $isPast,
                            'text-blue-600 dark:text-blue-400'    => $isCurrent,
                            'text-zinc-400 dark:text-zinc-500'    => $isFuture,
                        ])>
                            {{ $status->label() }}
                        </p>
                        @if ($isCurrent)
                            <p class="mt-0.5 text-xs text-zinc-400">Current status — updated {{ $report->updated_at->diffForHumans() }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Privacy reminder --}}
    <div class="flex items-start gap-3 rounded-xl bg-zinc-100 dark:bg-zinc-800/60 p-4">
        <flux:icon.lock-closed class="mt-0.5 size-5 shrink-0 text-zinc-400" />
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            For your safety, this page shows only the investigation stage — no personal details or case notes are disclosed here.
            If investigators need to contact you, they will use the method you provided (if any) at the time of submission.
        </p>
    </div>

    {{-- Actions --}}
    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <flux:button :href="route('report.track')" variant="ghost" class="w-full sm:w-auto">
            Track another report
        </flux:button>
        <flux:button :href="route('report.create')" variant="primary" class="w-full sm:w-auto">
            Submit a new report
        </flux:button>
    </div>

</main>

</body>
</html>
