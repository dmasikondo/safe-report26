@props([
    'title' => null,
    'description' => 'SafeReport lets you report corruption, fraud and other wrongdoing without creating an account or giving your name, and follow your report\'s progress with a private tracking token.',
])

{{--
    Shared shell for every public (guest) page: homepage, report submission
    and tracking, and staff authentication screens.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="{{ $description }}" />
    <title>{{ filled($title) ? $title.' — SafeReport' : 'SafeReport — Anonymous Whistleblowing' }}</title>
    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-brand-800 focus:shadow-lg">
    Skip to main content
</a>

<x-public.header />

<main id="main" class="relative isolate flex-1">
    <x-brand-backdrop />

    {{ $slot }}
</main>

<x-public.footer />

@persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>
