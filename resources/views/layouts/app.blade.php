<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="relative isolate">
        <x-brand-backdrop />

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
