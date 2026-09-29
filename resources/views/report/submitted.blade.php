<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Report Submitted — SafeReport</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

<header class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
            <flux:icon.shield-check class="size-5 text-blue-600" />
            SafeReport
        </a>
    </div>
</header>

<main class="mx-auto max-w-lg px-4 py-16 text-center">

    {{-- Success icon --}}
    <div class="mx-auto mb-6 flex size-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
        <flux:icon.check-circle class="size-10 text-green-600 dark:text-green-400" />
    </div>

    <h1 class="mb-2 text-2xl font-bold text-zinc-900 dark:text-white">Report received</h1>
    <p class="mb-10 text-zinc-500 dark:text-zinc-400">
        Thank you for speaking up. Your report has been securely submitted and will be reviewed by our team.
    </p>

    {{-- Tracking token card --}}
    <div class="mb-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 text-left shadow-sm">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400">Your tracking token</p>
        <div class="mt-2 flex items-center gap-2"
             x-data="{ copied: false }"
        >
            <code
                id="tracking-token"
                class="flex-1 rounded-lg bg-zinc-50 dark:bg-zinc-800 px-4 py-3 font-mono text-lg font-bold tracking-widest text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 select-all"
            >{{ $report->tracking_token }}</code>
            <button
                type="button"
                title="Copy token"
                class="flex size-11 shrink-0 items-center justify-center rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-500 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 dark:hover:border-blue-700 transition-colors"
                @click="
                    navigator.clipboard.writeText('{{ $report->tracking_token }}')
                        .then(() => { copied = true; setTimeout(() => copied = false, 2000); })
                        .catch(() => {
                            const el = document.getElementById('tracking-token');
                            const range = document.createRange();
                            range.selectNode(el);
                            window.getSelection().removeAllRanges();
                            window.getSelection().addRange(range);
                        });
                "
            >
                <flux:icon.clipboard-document class="size-5" x-show="!copied" />
                <flux:icon.clipboard-document-check class="size-5 text-green-500" x-show="copied" />
            </button>
        </div>
        <div
            x-data="{ copied: false }"
            class="mt-3 text-xs text-green-600 dark:text-green-400 font-medium transition-opacity"
            :class="copied ? 'opacity-100' : 'opacity-0'"
        >Copied to clipboard!</div>

        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
            <strong class="font-semibold text-zinc-700 dark:text-zinc-200">Save this token.</strong>
            It is the only way to check the status of your report. We cannot retrieve it if lost —
            it is not linked to your identity in any way.
        </p>
    </div>

    {{-- Report summary (no body content shown) --}}
    <div class="mb-8 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700 text-left overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 w-24 shrink-0">Category</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300 font-medium">{{ $report->category->label() }}</span>
        </div>
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 w-24 shrink-0">Status</span>
            <x-report.tracking-badge :report="$report" />
        </div>
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 w-24 shrink-0">Submitted</span>
            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $report->created_at->format('d M Y, H:i') }} UTC</span>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
        <flux:button
            :href="route('report.submitted', $report->tracking_token)"
            variant="ghost"
            class="w-full sm:w-auto"
        >
            <flux:icon.arrow-path class="mr-1.5 size-4" />
            Refresh status
        </flux:button>
        <flux:button
            :href="route('report.create')"
            variant="primary"
            class="w-full sm:w-auto"
        >
            Submit another report
        </flux:button>
    </div>

    <p class="mt-10 text-xs text-zinc-400">
        To check your report status later, go to
        <a href="{{ route('report.track') }}" class="underline hover:text-zinc-600 dark:hover:text-zinc-300">
            {{ route('report.track') }}
        </a>
        and enter your tracking token.
    </p>

</main>

</body>
</html>
