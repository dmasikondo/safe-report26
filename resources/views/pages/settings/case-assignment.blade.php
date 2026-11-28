<?php

use App\Models\Report;
use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Case assignment settings')] class extends Component {
    public bool $autoAssign = true;

    public function mount(): void
    {
        $this->authorize('configureAutoAssignment', Report::class);

        $this->autoAssign = Setting::autoAssignEnabled();
    }

    /**
     * Admins switch automatic case assignment on or off for the whole organisation.
     */
    public function updatedAutoAssign(bool $enabled): void
    {
        $this->authorize('configureAutoAssignment', Report::class);

        Setting::put(Setting::AUTO_ASSIGN_CASES, $enabled);

        Flux::toast(
            variant: 'success',
            text: $enabled ? __('Automatic assignment turned on.') : __('Automatic assignment turned off.'),
        );
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Case assignment settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Case assignment')" :subheading="__('Applies to everyone in the organisation')">
        <flux:switch
            wire:model.live="autoAssign"
            :label="__('Automatic case assignment')"
            :description="__('New reports go to the active investigator outside the report\'s department with the fewest open cases. Sexual harassment reports are never assigned automatically; admins are notified instead.')"
        />
    </x-pages::settings.layout>
</section>
