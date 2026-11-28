<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a staff account is created by an administrator.
 * Carries a password-reset token so the new staff member chooses their
 * own password; the administrator never learns it. Also sent to the new
 * address when an administrator changes a staff member's email.
 */
class StaffInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token, public string $roleLabel, public bool $afterEmailChange = false) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expiresInMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Your '.config('app.name').' staff account')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line($this->afterEmailChange
                ? "An administrator changed the email address of your {$this->roleLabel} account to this address."
                : "A staff account with the role of {$this->roleLabel} has been created for you.")
            ->line($this->afterEmailChange
                ? 'For your security you have been signed out. Choose a new password to continue.'
                : 'Choose a password to activate your access.')
            ->action('Set your password', $url)
            ->line("This link expires in {$expiresInMinutes} minutes. Ask an administrator to resend it if it expires.");
    }
}
