<?php

namespace App\Notifications;

use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an investigator a case is theirs, or tells their supervisor that a
 * case went to someone in their team.
 *
 * Never includes the report's subject, description or tracking token:
 * only the case reference, category and priority.
 */
class CaseAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Report $report,
        public User $assignee,
        public bool $automatic = false,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline($notifiable))
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line($this->message($notifiable))
            ->line('Category: '.$this->report->category->label())
            ->line('Priority: '.Report::labelForPriority($this->report->priority))
            ->action('Open the case', route('staff.reports.show', $this->report))
            ->line('Sign in to read the details. They are not included in this email to keep the report confidential.');
    }

    /**
     * The link is not stored: it is built from report_uuid when the notification
     * is opened, so stored notifications survive changes to the case URLs.
     *
     * @return array{report_uuid: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_uuid' => $this->report->uuid,
            'message' => $this->message($notifiable),
        ];
    }

    private function isForAssignee(object $notifiable): bool
    {
        return $notifiable->getKey() === $this->assignee->getKey();
    }

    private function headline(object $notifiable): string
    {
        return $this->isForAssignee($notifiable)
            ? "Case {$this->report->reference} has been assigned to you"
            : "Case {$this->report->reference} was assigned to your team";
    }

    private function message(object $notifiable): string
    {
        $how = $this->automatic ? ' automatically' : '';
        $category = $this->report->category->label();

        return $this->isForAssignee($notifiable)
            ? "Case {$this->report->reference} ({$category}) has been assigned to you{$how}."
            : "Case {$this->report->reference} ({$category}) was assigned{$how} to {$this->assignee->full_name} in your team.";
    }
}
