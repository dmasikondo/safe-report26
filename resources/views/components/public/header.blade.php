@php
    $linkClasses = 'items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-brand-500';
    $idleClasses = 'text-zinc-600 hover:bg-brand-50 hover:text-brand-800 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white';
    $currentClasses = 'bg-brand-50 text-brand-800 dark:bg-zinc-800 dark:text-white';

    $isTracking = request()->routeIs('report.track', 'report.track.submit');
    $isStaffArea = request()->routeIs('login', 'password.*', 'two-factor.*', 'verification.*');
@endphp

<header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/90">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-md font-semibold text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 dark:text-white">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-app-logo-icon class="size-5" />
            </span>
            SafeReport
        </a>

        <nav aria-label="Main" class="flex items-center gap-1 sm:gap-2">
            <a href="{{ route('report.track') }}"
               @if ($isTracking) aria-current="page" @endif
               class="hidden sm:inline-flex {{ $linkClasses }} {{ $isTracking ? $currentClasses : $idleClasses }}">
                <flux:icon.magnifying-glass variant="micro" class="size-4" />
                Track a report
            </a>
            <a href="{{ route('login') }}"
               @if ($isStaffArea) aria-current="page" @endif
               class="inline-flex {{ $linkClasses }} {{ $isStaffArea ? $currentClasses : $idleClasses }}">
                <flux:icon.lock-closed variant="micro" class="size-4" />
                Staff login
            </a>
            @unless (request()->routeIs('report.create'))
                <a href="{{ route('report.create') }}" class="hidden items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 md:inline-flex">
                    <flux:icon.document-plus variant="micro" class="size-4" />
                    Submit a report
                </a>
            @endunless
        </nav>
    </div>
</header>
