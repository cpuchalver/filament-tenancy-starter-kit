<?php

namespace App\Notifications;

use App\Filament\App\Pages\AcceptInvitation;
use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TeamInvitation $invitation,
        public string $token,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $teamName = $this->invitation->team->name;
        $inviterName = $this->invitation->invitedBy?->name;

        return (new MailMessage)
            ->subject(__('Join :team', ['team' => $teamName]))
            ->line($inviterName
                ? __(':inviter has invited you to join the :team team.', ['inviter' => $inviterName, 'team' => $teamName])
                : __('You have been invited to join the :team team.', ['team' => $teamName]))
            ->action(__('Accept invitation'), AcceptInvitation::getUrlForToken($this->token))
            ->line(__('This invitation expires on :date.', ['date' => $this->invitation->expires_at->toFormattedDayDateString()]))
            ->line(__('If you were not expecting this invitation, you can ignore this email.'));
    }
}
