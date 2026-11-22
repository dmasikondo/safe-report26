<footer class="bg-zinc-950 py-8 text-sm text-zinc-400">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p class="flex items-center gap-2">
            <x-app-logo-icon class="size-4 text-brand-400" />
            SafeReport, open-source whistleblowing infrastructure
        </p>
        <nav aria-label="Footer" class="flex flex-wrap gap-x-5 gap-y-2">
            <a href="{{ route('home') }}" class="hover:text-white">Home</a>
            <a href="{{ route('report.create') }}" class="hover:text-white">Submit a report</a>
            <a href="{{ route('report.track') }}" class="hover:text-white">Track a report</a>
            <a href="{{ route('login') }}" class="hover:text-white">Staff login</a>
            <a href="https://github.com/dmasikondo/safe-report26" class="hover:text-white" rel="noopener" target="_blank">Source code</a>
        </nav>
    </div>
</footer>
