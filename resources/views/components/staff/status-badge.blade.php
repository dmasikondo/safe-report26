{{--
    <x-staff.status-badge :member="$member" />
    Shows whether a staff account is active or deactivated.
--}}
@props(['member'])

<flux:badge size="sm" :color="$member->is_active ? 'green' : 'zinc'" {{ $attributes }}>
    {{ $member->is_active ? __('Active') : __('Deactivated') }}
</flux:badge>
