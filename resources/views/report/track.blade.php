<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Track a Report — SafeReport</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

<header class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
            <flux:icon.shield-check class="size-5 text-blue-600" />
            SafeReport
        </a>
        <a href="{{ route('report.create') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
            Submit a report →
        </a>
    </div>
</header>

<main class="mx-auto max-w-lg px-4 py-16">

    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
            <flux:icon.magnifying-glass class="size-7 text-blue-600 dark:text-blue-400" />
        </div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Track your report</h1>
        <p class="mt-2 text-zinc-500 dark:text-zinc-400">
            Enter the tracking token you received when you submitted your report.
        </p>
    </div>

    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 shadow-sm">
        <form method="POST" action="{{ route('report.track.submit') }}" novalidate>
            @csrf

            <div class="mb-5">
                <label for="token" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Tracking token
                </label>
                <input
                    id="token"
                    name="token"
                    type="text"
                    value="{{ old('token') }}"
                    placeholder="XXXXXXXX-XXXXXXXX"
                    autocomplete="off"
                    spellcheck="false"
                    class="w-full rounded-lg border @error('token') border-red-400 dark:border-red-600 @else border-zinc-300 dark:border-zinc-700 @enderror
                           bg-white dark:bg-zinc-800 px-3.5 py-2.5 font-mono text-sm tracking-wider
                           text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400
                           focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                    x-data
                    @input="$el.value = $el.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '')"
                />
                <x-report.field-error name="token" />
                <p class="mt-1.5 text-xs text-zinc-400">
                    Your token was shown on the confirmation page after submission. It looks like <code class="font-mono">AB12CD34-EF56GH78</code>.
                </p>
            </div>

            <flux:button type="submit" variant="primary" class="w-full">
                <flux:icon.magnifying-glass class="mr-1.5 size-4" />
                Look up report
            </flux:button>
        </form>
    </div>

    <div class="mt-6 flex items-start gap-3 rounded-xl bg-zinc-100 dark:bg-zinc-800/60 p-4">
        <flux:icon.information-circle class="mt-0.5 size-5 shrink-0 text-zinc-400" />
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            Tracking tokens are not stored against your identity. We cannot look up your report on your behalf —
            only the person who holds the token can view it.
        </p>
    </div>

</main>

</body>
</html>
