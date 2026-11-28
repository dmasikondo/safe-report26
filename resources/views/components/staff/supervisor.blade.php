{{--
    <x-staff.supervisor :member="$member" />
    Shows an investigator's supervisor (linked to their profile where allowed),
    "Unassigned" when they have none, or a dash for other roles.
--}}
@props(['member'])

@if ($member->isInvestigator())
    <x-staff.user-link :user="$member->supervisor" :fallback="__('Unassigned')" />
@else
    —
@endif
