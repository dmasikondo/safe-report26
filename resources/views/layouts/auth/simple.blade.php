<x-layouts::public :title="$title ?? null">
    <div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-12 sm:py-16">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="flex size-12 items-center justify-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-900/20">
                <x-app-logo-icon class="size-7" />
            </span>
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">
                {{ __('SafeReport staff area') }}
            </p>
        </div>

        <div class="flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            {{ $slot }}
        </div>

        <p class="flex items-start justify-center gap-2 text-center text-xs text-zinc-500 dark:text-zinc-400">
            <flux:icon.information-circle variant="micro" class="mt-px size-4 shrink-0" />
            <span>
                {{ __('Want to report wrongdoing? You do not need an account.') }}
                <a href="{{ route('report.create') }}" class="font-medium text-brand-700 underline-offset-2 hover:underline dark:text-brand-300">{{ __('Submit a report anonymously') }}</a>.
            </span>
        </p>
    </div>
</x-layouts::public>
