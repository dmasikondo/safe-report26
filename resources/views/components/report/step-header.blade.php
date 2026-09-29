{{--
    <x-report.step-header
        :step="1"
        :total="3"
        title="Category"
        description="What kind of misconduct are you reporting?"
    />
--}}
@props(['step', 'total', 'title', 'description' => null])

<div class="mb-6">
    {{-- Step indicator --}}
    <div class="mb-3 flex items-center gap-2">
        @for ($i = 1; $i <= $total; $i++)
            <div @class([
                'h-1.5 flex-1 rounded-full transition-colors duration-300',
                'bg-blue-600 dark:bg-blue-500'  => $i <= $step,
                'bg-zinc-200 dark:bg-zinc-700'  => $i > $step,
            ])></div>
        @endfor
    </div>

    <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">
        Step {{ $step }} of {{ $total }}
    </p>
    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
        {{ $title }}
    </h2>
    @if ($description)
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif
</div>
