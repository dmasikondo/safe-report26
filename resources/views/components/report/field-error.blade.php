{{--
    <x-report.field-error name="body" />
    Shows the first validation error for the given field name.
--}}
@props(['name'])

@error($name)
    <p class="mt-1.5 flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400">
        <flux:icon.exclamation-circle class="size-4 shrink-0" />
        {{ $message }}
    </p>
@enderror
