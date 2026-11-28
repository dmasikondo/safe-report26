{{--
    <x-staff.member-actions :member="$member" />
    <x-staff.member-actions :member="$member" full-width />

    Resend-invite and activate/deactivate buttons for a staff member, shown only where the viewer is allowed.
    The buttons call resendInvitation() and toggleActive() on the surrounding Livewire page,
    which gets them from App\Concerns\ManagesStaffAccounts.
    Use full-width on small screens so the buttons are easy to tap.
--}}
@props(['member', 'fullWidth' => false])

<div {{ $attributes->class(['flex gap-2', 'justify-end' => ! $fullWidth]) }}>
    @can('resendInvitation', $member)
        <flux:button
            :size="$fullWidth ? 'sm' : 'xs'"
            variant="ghost"
            wire:click="resendInvitation('{{ $member->uuid }}')"
            @class(['flex-1' => $fullWidth])
        >
            {{ __('Resend invite') }}
        </flux:button>
    @endcan
    @can('deactivate', $member)
        <flux:button
            :size="$fullWidth ? 'sm' : 'xs'"
            :variant="$member->is_active ? 'danger' : 'filled'"
            wire:click="toggleActive('{{ $member->uuid }}')"
            wire:confirm="{{ $member->is_active ? __('Deactivate this account? They will be signed out immediately.') : __('Reactivate this account?') }}"
            @class(['flex-1' => $fullWidth])
        >
            {{ $member->is_active ? __('Deactivate') : __('Activate') }}
        </flux:button>
    @endcan
</div>
