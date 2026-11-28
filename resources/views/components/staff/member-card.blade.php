{{--
    <x-staff.member-card :member="$member" />
    Small-screen card for one row of the staff list. Expects roles, department and supervisor to be eager loaded.
--}}
@props(['member'])

<x-list-card {{ $attributes }}>
    <x-slot:title><x-staff.user-link :user="$member" /></x-slot:title>
    <x-slot:subtitle>{{ $member->email }}</x-slot:subtitle>
    <x-slot:badge><x-staff.status-badge :member="$member" /></x-slot:badge>

    <x-list-card.detail :label="__('Role')">{{ $member->roleLabels() ?: '—' }}</x-list-card.detail>
    <x-list-card.detail :label="__('Department')">{{ $member->department?->name ?? '—' }}</x-list-card.detail>
    <x-list-card.detail :label="__('Supervisor')"><x-staff.supervisor :member="$member" /></x-list-card.detail>
    <x-list-card.detail :label="__('Last sign-in')">{{ $member->last_login_at?->diffForHumans() ?? __('Never') }}</x-list-card.detail>

    @canany(['editStaffAccount', 'resendInvitation', 'deactivate'], $member)
        <x-slot:actions><x-staff.member-actions :member="$member" full-width /></x-slot:actions>
    @endcanany
</x-list-card>
