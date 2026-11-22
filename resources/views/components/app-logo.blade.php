@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="SafeReport" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand-600 text-white dark:bg-gold-300 dark:text-brand-950">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="SafeReport" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand-600 text-white dark:bg-gold-300 dark:text-brand-950">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
