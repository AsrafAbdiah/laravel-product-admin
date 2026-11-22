<?php

namespace App\Notifications;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    /**
     * The plain token is only ever in this email; the database keeps a hash.
     */
    public function __construct(public TeamInvitation $invitation, public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $team = $this->invitation->team;
        $inviter = $this->invitation->inviter->name ?? 'Someone';

        return (new MailMessage)
            ->subject("Join {$team->name} on ".config('app.name'))
            ->greeting('Hello!')
            ->line("{$inviter} has invited you to join **{$team->name}** as **{$this->invitation->role}**.")
            ->action('Accept invitation', route('invitations.show', $this->token))
            ->line('Log in or create an account with this email address ('.$this->invitation->email.') to accept.')
            ->line('This invitation expires on '.$this->invitation->expires_at->format('j M Y').'.');
    }
}
