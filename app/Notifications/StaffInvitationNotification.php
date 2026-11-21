<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a staff account is created by an administrator.
 * Carries a password-reset token so the new staff member chooses their
 * own password; the administrator never learns it.
 */
class StaffInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token, public string $roleLabel) {}

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
            ->line("A staff account with the role of {$this->roleLabel} has been created for you.")
            ->line('Choose a password to activate your access.')
            ->action('Set your password', $url)
            ->line("This link expires in {$expiresInMinutes} minutes. Ask an administrator to resend it if it expires.");
    }
}
