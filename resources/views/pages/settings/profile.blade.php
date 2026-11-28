<?php

use App\Traits\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->first_name = Auth::user()->first_name;
        $this->last_name = Auth::user()->last_name;
        $this->email = Auth::user()->email;
    }

    /**
     * Whether the signed-in user may change their own email address here.
     * Only superadmins can; other staff have it changed by an administrator
     * from the staff area, which re-invites the account.
     */
    #[Computed]
    public function canChangeEmail(): bool
    {
        return Auth::user()->can('changeOwnEmail', User::class);
    }

    /**
     * Update the signed-in user's name, and their email address when they are
     * allowed to change it. Otherwise the email sent with the form is ignored.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $rules = [
            'first_name' => $this->nameRules(),
            'last_name' => $this->nameRules(),
        ];

        if ($this->canChangeEmail) {
            $rules['email'] = $this->emailRules($user->id);
        }

        $validated = $this->validate($rules);

        $user->update($validated);
        $this->email = $user->email;

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="$this->canChangeEmail ? __('Update your name and email address') : __('Update your name')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('First name')" type="text" required autofocus autocomplete="given-name" />
                <flux:input wire:model="last_name" :label="__('Last name')" type="text" required autocomplete="family-name" />
            </div>

            <div>
                @if ($this->canChangeEmail)
                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
                @else
                    <flux:input wire:model="email" :label="__('Email')" type="email" readonly disabled autocomplete="email" />

                    @unless (auth()->user()->isAdmin())
                        <flux:text class="mt-2 text-sm">{{ __('Only an administrator can change your email address.') }}</flux:text>
                    @endunless
                @endif

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>
    </x-pages::settings.layout>
</section>
