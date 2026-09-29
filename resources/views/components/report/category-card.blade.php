{{--
    <x-report.category-card :category="ReportCategoryEnum::CORRUPTION" :selected="false" />

    Alpine.js-driven selectable card for step 1 of the submission wizard.
    Emits an Alpine event 'category-selected' with the value, and also
    sets the hidden input directly so the parent can read it on submit.

    Designed to be used inside an @foreach over ReportCategoryEnum::cases().
--}}
@props(['category', 'selected' => false])

<label
    class="group relative flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 p-5 text-center transition-all duration-200"
    :class="selectedCategory === '{{ $category->value }}'
        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40 shadow-md'
        : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-700 bg-white dark:bg-zinc-900'"
    @click="selectedCategory = '{{ $category->value }}'"
>
    {{-- Hidden radio input so the value is included in the form data --}}
    <input
        type="radio"
        name="category"
        value="{{ $category->value }}"
        class="sr-only"
        x-bind:checked="selectedCategory === '{{ $category->value }}'"
        {{ $selected ? 'checked' : '' }}
    />

    {{-- Category icon --}}
    <span
        class="flex size-12 items-center justify-center rounded-full transition-colors duration-200"
        :class="selectedCategory === '{{ $category->value }}'
            ? 'bg-blue-100 dark:bg-blue-900/60 text-blue-600 dark:text-blue-400'
            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 group-hover:bg-blue-50 dark:group-hover:bg-blue-900/30 group-hover:text-blue-500'"
    >
        <flux:icon.{{ $category->icon() }} class="size-6" />
    </span>

    {{-- Category label --}}
    <span
        class="text-sm font-medium leading-tight transition-colors duration-200"
        :class="selectedCategory === '{{ $category->value }}'
            ? 'text-blue-700 dark:text-blue-300'
            : 'text-zinc-700 dark:text-zinc-300'"
    >
        {{ $category->label() }}
    </span>

    {{-- Selected checkmark --}}
    <span
        class="absolute right-2.5 top-2.5 flex size-5 items-center justify-center rounded-full bg-blue-600 text-white transition-opacity duration-200"
        :class="selectedCategory === '{{ $category->value }}' ? 'opacity-100' : 'opacity-0'"
        aria-hidden="true"
    >
        <svg class="size-3" viewBox="0 0 12 12" fill="none">
            <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
</label>
