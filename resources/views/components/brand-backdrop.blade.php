{{--
    Soft brand-coloured glow behind the top of a page. Decorative only.
    Place inside a `relative isolate` container.
--}}
<div aria-hidden="true" {{ $attributes->class('pointer-events-none absolute inset-x-0 top-0 -z-10 h-[40rem] overflow-hidden') }}>
    <div class="absolute -left-32 -top-32 size-[28rem] rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-800/25"></div>
    <div class="absolute -right-24 top-40 size-[22rem] rounded-full bg-gold-200/60 blur-3xl dark:bg-gold-700/10"></div>
</div>
