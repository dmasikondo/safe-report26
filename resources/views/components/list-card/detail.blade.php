{{--
    <x-list-card.detail :label="__('Department')">Finance</x-list-card.detail>
    One label/value row inside an <x-list-card>.
--}}
@props(['label'])

<dt class="text-zinc-500">{{ $label }}</dt>
<dd {{ $attributes->merge(['class' => 'min-w-0 break-words text-zinc-800 dark:text-zinc-200']) }}>{{ $slot }}</dd>
