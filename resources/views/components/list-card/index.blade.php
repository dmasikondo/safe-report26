{{--
    <x-list-card>
        <x-slot:title>Primary line</x-slot:title>
        <x-slot:subtitle>Secondary line</x-slot:subtitle>
        <x-slot:badge><flux:badge>Status</flux:badge></x-slot:badge>

        <x-list-card.detail :label="__('Label')">Value</x-list-card.detail>

        <x-slot:actions>Buttons</x-slot:actions>
    </x-list-card>

    Card layout for showing one record of a list on small screens, where a table would scroll sideways.
--}}
@props(['title', 'subtitle' => null, 'badge' => null, 'actions' => null])

<flux:card {{ $attributes->merge(['class' => 'flex flex-col gap-4 p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="font-medium break-words">{{ $title }}</div>

            @if ($subtitle?->hasActualContent())
                <div class="text-xs break-all text-zinc-500">{{ $subtitle }}</div>
            @endif
        </div>

        @if ($badge?->hasActualContent())
            <div class="shrink-0">{{ $badge }}</div>
        @endif
    </div>

    @if ($slot->hasActualContent())
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
            {{ $slot }}
        </dl>
    @endif

    @if ($actions?->hasActualContent())
        <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
            {{ $actions }}
        </div>
    @endif
</flux:card>
