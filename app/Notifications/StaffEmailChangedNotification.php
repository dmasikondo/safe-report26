<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a staff member's previous email address after an administrator
 * changes it, so an unexpected change (a possible account takeover) is noticed.
 * The new address is deliberately not included.
 */
class StaffEmailChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $firstName) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.config('app.name').' account email was changed')
            ->greeting('Hello '.$this->firstName.',')
            ->line('An administrator changed the email address of your '.config('app.name').' staff account. This address can no longer be used to sign in.')
            ->line('You have been signed out, and a link to choose a new password was sent to the new address.')
            ->line('If you did not expect this change, contact your system administrator immediately.');
    }
}
