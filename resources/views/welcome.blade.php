<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="SafeReport lets you report corruption, fraud and other wrongdoing without creating an account or giving your name, and follow your report's progress with a private tracking token." />
    <title>SafeReport — Anonymous Whistleblowing</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-brand-800 focus:shadow-lg">
    Skip to main content
</a>

{{-- ── Header ──────────────────────────────────────────────────────────── --}}
<header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/90">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-md font-semibold text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 dark:text-white">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                <flux:icon.shield-check variant="mini" class="size-5" />
            </span>
            SafeReport
        </a>

        <nav aria-label="Main" class="flex items-center gap-1 sm:gap-2">
            <a href="{{ route('report.track') }}" class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-brand-50 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-brand-500 sm:inline-flex dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
                <flux:icon.magnifying-glass variant="micro" class="size-4" />
                Track a report
            </a>
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-brand-50 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-brand-500 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
                <flux:icon.lock-closed variant="micro" class="size-4" />
                Staff login
            </a>
            <a href="{{ route('report.create') }}" class="hidden items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 md:inline-flex">
                <flux:icon.document-plus variant="micro" class="size-4" />
                Submit a report
            </a>
        </nav>
    </div>
</header>

<main id="main">

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <section aria-labelledby="hero-heading" class="relative overflow-hidden">
        {{-- Soft brand glow, decorative only --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -left-32 -top-32 size-[28rem] rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/40"></div>
            <div class="absolute -right-24 top-40 size-[22rem] rounded-full bg-gold-200/60 blur-3xl dark:bg-gold-900/20"></div>
        </div>

        <div class="mx-auto grid max-w-6xl items-start gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:gap-x-12 lg:gap-y-8 lg:py-20">

            {{-- Left: what SafeReport is --}}
            <div class="lg:col-span-7">
                <p class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-700 dark:border-brand-800 dark:bg-brand-950/50 dark:text-brand-300">
                    <flux:icon.eye-slash variant="micro" class="size-3.5" />
                    Anonymous whistleblowing
                </p>

                <h1 id="hero-heading" class="mt-5 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                    SafeReport
                    <span class="block text-brand-700 dark:text-brand-300">Speak up about wrongdoing, safely.</span>
                </h1>

                <p class="mt-5 max-w-xl text-lg leading-relaxed text-zinc-600 dark:text-zinc-300">
                    Corruption, fraud and abuse of power thrive when people are afraid to speak.
                    SafeReport gives you a secure way to report what you have seen to authorised
                    investigators without creating an account or giving your name, and to follow
                    what happens next.
                </p>

            </div>

            {{-- Right: primary actions --}}
            <div class="flex flex-col gap-4 lg:col-span-5 lg:col-start-8 lg:row-span-2 lg:row-start-1">

                {{-- Submit a report --}}
                <a href="{{ route('report.create') }}"
                   class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white shadow-lg shadow-brand-900/20 transition hover:-translate-y-0.5 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 sm:p-7">
                    {{-- Decorative shield illustration --}}
                    <svg aria-hidden="true" viewBox="0 0 120 140" class="absolute -bottom-6 -right-4 h-36 w-auto text-white/10" fill="currentColor">
                        <path d="M60 0 L120 22 V66 C120 104 94 128 60 140 C26 128 0 104 0 66 V22 Z"/>
                        <path d="M38 70 L54 86 L86 52" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" class="text-white/20"/>
                    </svg>

                    <span class="flex size-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <flux:icon.document-plus class="size-6" />
                    </span>
                    <h2 class="mt-5 text-2xl font-bold">Submit a report</h2>
                    <p class="mt-2 max-w-xs text-sm leading-relaxed text-brand-50">
                        Tell us what happened in three short steps. You do not need to give your name.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-2 rounded-lg bg-gold-300 px-4 py-2.5 text-sm font-semibold text-brand-950 transition group-hover:bg-gold-200">
                        Start a report
                        <flux:icon.arrow-right variant="micro" class="size-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>

                {{-- Track a report --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-7">
                    <div class="flex items-start gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gold-100 text-gold-800 dark:bg-gold-900/40 dark:text-gold-300">
                            <flux:icon.magnifying-glass class="size-6" />
                        </span>
                        <div>
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Track a report</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Already reported something? Enter your tracking token to see its status.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('report.track.submit') }}" class="mt-5" novalidate>
                        @csrf
                        <label for="home-token" class="sr-only">Tracking token</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input
                                id="home-token"
                                name="token"
                                type="text"
                                value="{{ old('token') }}"
                                placeholder="AB12CD34-EF56GH78"
                                autocomplete="off"
                                spellcheck="false"
                                @error('token') aria-invalid="true" aria-describedby="home-token-error" @enderror
                                class="min-w-0 flex-1 rounded-lg border bg-white px-3.5 py-2.5 font-mono text-sm tracking-wider text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 dark:bg-zinc-800 dark:text-zinc-100 @error('token') border-red-400 dark:border-red-600 @else border-zinc-300 dark:border-zinc-700 @enderror"
                                x-data
                                @input="$el.value = $el.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '')"
                            />
                            <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-600 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-600 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 dark:border-gold-300 dark:text-gold-300 dark:hover:bg-gold-300 dark:hover:text-brand-950">
                                Check status
                            </button>
                        </div>
                        @error('token')
                            <p id="home-token-error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </form>
                    <a href="{{ route('report.track') }}" class="mt-3 inline-block text-sm font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300">
                        Where do I find my token?
                    </a>
                </div>

                {{-- Staff login --}}
                <a href="{{ route('login') }}"
                   class="group flex items-center gap-4 rounded-2xl border border-dashed border-zinc-300 bg-white/60 p-4 transition hover:border-brand-400 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:hover:border-brand-600">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.lock-closed variant="mini" class="size-5" />
                    </span>
                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-zinc-900 dark:text-white">Staff login</span>
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">For authorised investigators and administrators only</span>
                    </span>
                    <flux:icon.arrow-right variant="micro" class="size-4 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                </a>
            </div>

            {{-- Key benefits (below the actions on small screens) --}}
            <div class="lg:col-span-7">
                <ul class="grid gap-4 sm:grid-cols-2" role="list">
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
                            <flux:icon.user-minus variant="mini" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">No account, no name</p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">You never sign up or log in. Reports are not linked to a user account.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gold-100 text-gold-800 dark:bg-gold-900/40 dark:text-gold-300">
                            <flux:icon.key variant="mini" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">A private tracking token</p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Only the person holding the token can check a report's progress.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
                            <flux:icon.eye-slash variant="mini" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Your connection is not traced</p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">We do not record your internet address or device details, and you can reach us through the Tor Browser.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gold-100 text-gold-800 dark:bg-gold-900/40 dark:text-gold-300">
                            <flux:icon.lock-closed variant="mini" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Locked with strong encryption</p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Your report is scrambled before it is saved, so only authorised investigators can read it.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
                            <flux:icon.link variant="mini" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Records that cannot be secretly changed</p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">A blockchain-inspired chain of digital seals shows if anyone alters or deletes a record.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gold-100 text-gold-800 dark:bg-gold-900/40 dark:text-gold-300">
                            <flux:icon.code-bracket variant="mini" class="size-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Open source</p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Anyone can inspect how the platform works. There is no hidden "black box".</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ── How it works ─────────────────────────────────────────────────── --}}
    <section aria-labelledby="how-heading" class="border-y border-zinc-200 bg-white py-16 dark:border-zinc-800 dark:bg-zinc-900 sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-gold-700 dark:text-gold-300">How it works</p>
                <h2 id="how-heading" class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">From your report to an investigation</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">Four simple steps. You stay in control of the only key to your report.</p>
            </div>

            @php
                $steps = [
                    ['icon' => 'document-plus', 'title' => 'Submit your report', 'text' => 'Choose a category and describe what happened: who, what, when and where. You do not need to give your name.'],
                    ['icon' => 'key', 'title' => 'Save your tracking token', 'text' => 'When you submit, you receive a unique token. Write it down or store it somewhere safe. It is the only way to follow up, and it cannot be recovered if lost.'],
                    ['icon' => 'clipboard-document-check', 'title' => 'Review and investigation', 'text' => 'Authorised staff receive and review your report, and assign it to an investigator who looks into it.'],
                    ['icon' => 'chart-bar', 'title' => 'Track the progress', 'text' => 'Enter your token at any time to see the current status of your report as it moves through the process.'],
                ];
            @endphp

            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                @foreach ($steps as $step)
                    <li class="relative rounded-2xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-3">
                            <span class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                            <flux:icon :name="$step['icon']" class="size-6 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                        </div>
                        <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">
                            <span class="sr-only">Step {{ $loop->iteration }}: </span>{{ $step['title'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>

            {{-- Real status stages, taken from the application --}}
            <div class="mt-10 rounded-2xl bg-brand-50 p-5 dark:bg-brand-950/40 sm:p-6">
                <p class="text-sm font-semibold text-brand-900 dark:text-brand-200">The stages you will see when tracking</p>
                <ol class="mt-3 flex flex-wrap items-center gap-2" role="list">
                    @foreach (App\Enums\ReportStatusEnum::timeline() as $status)
                        <li class="flex items-center gap-2">
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200 dark:bg-brand-900/60 dark:text-brand-100 dark:ring-brand-800">{{ $status->label() }}</span>
                            @unless ($loop->last)
                                <flux:icon.chevron-right variant="micro" class="size-4 text-brand-400" aria-hidden="true" />
                            @endunless
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- ── What you can report ──────────────────────────────────────────── --}}
    <section aria-labelledby="categories-heading" class="py-16 sm:py-20">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-5">
                <p class="text-sm font-semibold uppercase tracking-wider text-gold-700 dark:text-gold-300">What you can report</p>
                <h2 id="categories-heading" class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">If something is wrong, it is worth reporting</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                    You do not need proof or certainty to make a report. Describe what you know honestly,
                    and investigators will assess it.
                </p>
            </div>
            <ul class="grid gap-3 sm:grid-cols-2 lg:col-span-7" role="list">
                @foreach (App\Enums\ReportCategoryEnum::cases() as $category)
                    <li class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                        <flux:icon.check-circle variant="mini" class="size-5 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                        {{ $category->label() }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ── Security, explained in plain language ────────────────────────── --}}
    <section aria-labelledby="security-heading" class="border-t border-zinc-200 bg-white py-16 dark:border-zinc-800 dark:bg-zinc-900 sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-gold-700 dark:text-gold-300">How we keep you safe</p>
                <h2 id="security-heading" class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">The protections behind every report</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                    You do not need to understand technology to use SafeReport safely. Here is what each
                    protection does, explained in everyday terms.
                </p>
            </div>

            @php
                $protections = [
                    [
                        'icon' => 'globe-alt',
                        'title' => 'Tor Browser support',
                        'what' => 'SafeReport can be reached through the Tor Browser, a free web browser built to hide where you are connecting from.',
                        'plain' => 'Think of posting a letter through several post offices, where each one only knows the office before and after it. By the time your report reaches us, nobody along the way, including us, can tell where it came from.',
                    ],
                    [
                        'icon' => 'finger-print',
                        'title' => 'No IP address or fingerprint records',
                        'what' => 'We do not store your IP address or a "fingerprint" of your browser.',
                        'plain' => 'An IP address is like the return address of your internet connection. A browser fingerprint is a collection of small details, such as your screen size and settings, that can identify a device. We do not write down either, so there is no trail leading back to you.',
                    ],
                    [
                        'icon' => 'lock-closed',
                        'title' => 'Military-grade encryption',
                        'what' => 'Your report and your optional contact method are encrypted with AES-256-GCM before they are saved.',
                        'plain' => 'Encryption scrambles your words into meaningless characters, like locking them in a safe with a very long combination. Even if someone stole our database, they would see only gibberish. The "GCM" part also acts like a tamper seal: if anyone changes the scrambled data, it will refuse to unlock.',
                    ],
                    [
                        'icon' => 'link',
                        'title' => 'Blockchain-inspired integrity',
                        'what' => 'Every action on a report is recorded in a chained, tamper-evident log protected by SHA-256 digital fingerprints.',
                        'plain' => 'Each entry gets a unique digital seal that is calculated from its own contents and the seal of the entry before it, like links in a chain. If anyone secretly changes or deletes an old entry, its seal no longer matches and every link after it breaks, so tampering is immediately visible. It borrows this idea from blockchain, but it has nothing to do with cryptocurrency.',
                    ],
                    [
                        'icon' => 'code-bracket',
                        'title' => 'Open source',
                        'what' => 'The complete source code of SafeReport is public.',
                        'plain' => 'Anyone, including independent security experts, can inspect exactly how the platform works and confirm that it does what it says. There is no hidden "black box" you are asked to trust blindly.',
                    ],
                    [
                        'icon' => 'user-group',
                        'title' => 'Strict staff access',
                        'what' => 'Only authorised staff can open reports, and investigators only see the cases assigned to them.',
                        'plain' => 'It works like a records room where each investigator is given keys only to their own files. Every time someone changes the status of your report, assigns it or adds a note, it is recorded in the chained log above.',
                    ],
                ];
            @endphp

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($protections as $protection)
                    <article class="flex flex-col rounded-2xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <span class="flex size-11 items-center justify-center rounded-xl {{ $loop->odd ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300' : 'bg-gold-100 text-gold-800 dark:bg-gold-900/40 dark:text-gold-300' }}">
                            <flux:icon :name="$protection['icon']" class="size-6" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $protection['title'] }}</h3>
                        <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $protection['what'] }}</p>
                        <div class="mt-4 rounded-xl bg-white p-4 text-sm leading-relaxed text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-400 dark:ring-zinc-800">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">In plain terms</p>
                            {{ $protection['plain'] }}
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- How to use Tor --}}
            <div class="mt-12 grid gap-8 rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-100 dark:bg-brand-950/40 dark:ring-brand-900 sm:p-8 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <h3 class="flex items-center gap-2 text-xl font-bold text-brand-900 dark:text-brand-100">
                        <flux:icon.globe-alt class="size-6 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                        Reporting through Tor, step by step
                    </h3>
                    <p class="mt-2 text-sm text-brand-900/80 dark:text-brand-200">
                        Using Tor is optional, but it gives you the strongest protection, especially if you are
                        worried that your internet connection is being watched.
                    </p>
                </div>
                <ol class="space-y-4 text-sm text-brand-950 dark:text-brand-100 lg:col-span-3" role="list">
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white" aria-hidden="true">1</span>
                        <span>
                            Download the free Tor Browser from its official website,
                            <a href="https://www.torproject.org/download/" class="font-semibold text-brand-700 underline underline-offset-2 dark:text-brand-300" rel="noopener noreferrer" target="_blank">torproject.org</a>.
                            Only download it from there.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white" aria-hidden="true">2</span>
                        <span>Open the Tor Browser and click <strong>Connect</strong>. It may take a moment to start.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white" aria-hidden="true">3</span>
                        <span>
                            @if (filled(config('safereport.onion_url')))
                                Type our private Tor address into the Tor Browser:
                                <code class="mt-1 block break-all rounded-lg bg-white px-3 py-2 font-mono text-xs text-brand-900 ring-1 ring-brand-200 dark:bg-brand-950 dark:text-brand-100 dark:ring-brand-800">{{ config('safereport.onion_url') }}</code>
                            @else
                                Visit SafeReport in the Tor Browser using the address published by your organisation.
                            @endif
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white" aria-hidden="true">4</span>
                        <span>Submit or track your report exactly as you would in any other browser, then close the Tor Browser when you are done.</span>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    {{-- ── Privacy ──────────────────────────────────────────────────────── --}}
    <section aria-labelledby="privacy-heading" class="bg-brand-950 py-16 text-white sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-gold-300">Your privacy</p>
                <h2 id="privacy-heading" class="mt-2 text-3xl font-bold tracking-tight">Protecting your identity</h2>
                <p class="mt-3 text-brand-100">
                    SafeReport is designed so you can report without revealing who you are. Your anonymity
                    also depends on what you write and how you connect, so please read both lists below.
                </p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
                    <h3 class="flex items-center gap-2 font-semibold">
                        <flux:icon.shield-check variant="mini" class="size-5 text-gold-300" aria-hidden="true" />
                        What SafeReport does
                    </h3>
                    <ul class="mt-4 space-y-3 text-sm text-brand-100" role="list">
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Asks for no account, sign-in or name to submit or track a report.</li>
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Does not record your IP address or browser fingerprint, and works through the Tor Browser.</li>
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Encrypts your report and your optional contact method before storing them.</li>
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Seals every record in a tamper-evident chain, so any later change or deletion shows up.</li>
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Does not link your tracking token to your identity. Only the token holder can view a report's status.</li>
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Restricts reports to authorised staff, and investigators only see the cases assigned to them.</li>
                        <li class="flex gap-2"><flux:icon.check variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Keeps staff notes about your case internal. They are never shown on the tracking page.</li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-gold-300/10 p-6 ring-1 ring-gold-300/30">
                    <h3 class="flex items-center gap-2 font-semibold">
                        <flux:icon.light-bulb variant="mini" class="size-5 text-gold-300" aria-hidden="true" />
                        How you can protect yourself
                    </h3>
                    <ul class="mt-4 space-y-3 text-sm text-brand-100" role="list">
                        <li class="flex gap-2"><flux:icon.arrow-right variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Leave out details only you would know, such as your name, job title or exact role in events.</li>
                        <li class="flex gap-2"><flux:icon.arrow-right variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Use the Tor Browser on a personal device, and avoid your employer's network or computers.</li>
                        <li class="flex gap-2"><flux:icon.arrow-right variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />The contact method is optional. If you give one, use an account that is not linked to your real name.</li>
                        <li class="flex gap-2"><flux:icon.arrow-right variant="micro" class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />Keep your tracking token private. Anyone who has it can see your report's status.</li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('report.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gold-300 px-5 py-3 text-sm font-semibold text-brand-950 transition hover:bg-gold-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-300">
                    <flux:icon.document-plus variant="micro" class="size-4" />
                    Submit a report
                </a>
                <a href="{{ route('report.track') }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-300">
                    <flux:icon.magnifying-glass variant="micro" class="size-4" />
                    Track a report
                </a>
            </div>
        </div>
    </section>
</main>

{{-- ── Footer ──────────────────────────────────────────────────────────── --}}
<footer class="bg-zinc-950 py-8 text-sm text-zinc-400">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p class="flex items-center gap-2">
            <flux:icon.shield-check variant="mini" class="size-4 text-brand-400" aria-hidden="true" />
            SafeReport, open-source whistleblowing infrastructure
        </p>
        <nav aria-label="Footer" class="flex flex-wrap gap-x-5 gap-y-2">
            <a href="{{ route('report.create') }}" class="hover:text-white">Submit a report</a>
            <a href="{{ route('report.track') }}" class="hover:text-white">Track a report</a>
            <a href="{{ route('login') }}" class="hover:text-white">Staff login</a>
            <a href="https://github.com/dmasikondo/safe-report26" class="hover:text-white" rel="noopener" target="_blank">Source code</a>
        </nav>
    </div>
</footer>

</body>
</html>
