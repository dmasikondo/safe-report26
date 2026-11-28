{{--
    <x-staff.user-link :user="$user" />
    <x-staff.user-link :user="$report->assignee" :fallback="__('Unassigned')" />

    A staff member's name, linked to their profile when the viewer is allowed to open it
    and plain text otherwise. Shows the fallback (muted) when there is no user.
--}}
@props(['user', 'fallback' => '—'])

@php
    // Load the viewer's roles once, so a list of links does not query them for every permission check.
    $viewer = auth()->user()?->loadMissing('roles');
@endphp

@if ($user === null)
    <span {{ $attributes->class('text-zinc-400') }}>{{ $fallback }}</span>
@elseif ($viewer?->can('view', $user))
    <flux:link :href="route('staff.users.show', $user)" wire:navigate variant="subtle" {{ $attributes }}>{{ $user->full_name }}</flux:link>
@else
    <span {{ $attributes }}>{{ $user->full_name }}</span>
@endif
