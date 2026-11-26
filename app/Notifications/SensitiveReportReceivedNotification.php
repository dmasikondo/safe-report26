<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells administrators a new report needs someone to assign it by hand:
 * sexual harassment reports (never auto-assigned) or reports for which no
 * investigator was eligible.
 *
 * Never includes the report's subject, description or tracking token.
 */
class SensitiveReportReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SEXUAL_HARASSMENT = 'sexual_harassment';

    public const NO_ELIGIBLE_INVESTIGATOR = 'no_eligible_investigator';

    /**
     * @param  self::SEXUAL_HARASSMENT|self::NO_ELIGIBLE_INVESTIGATOR  $reason
     */
    public function __construct(
        public Report $report,
        public string $reason,
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
            ->subject("Case #{$this->report->id} needs manual assignment")
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line($this->message())
            ->line('Priority: '.Report::labelForPriority($this->report->priority))
            ->action('Open the case', route('staff.reports.show', $this->report))
            ->line('Sign in to read the details. They are not included in this email to keep the report confidential.');
    }

    /**
     * @return array{report_id: int, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'message' => $this->message(),
            'url' => route('staff.reports.show', $this->report),
        ];
    }

    private function message(): string
    {
        return match ($this->reason) {
            self::SEXUAL_HARASSMENT => "A sexual harassment report (case #{$this->report->id}) was received. These are never assigned automatically; please choose who handles it.",
            default => "Case #{$this->report->id} ({$this->report->category->label()}) could not be assigned automatically because no investigator outside its department is available.",
        };
    }
}
