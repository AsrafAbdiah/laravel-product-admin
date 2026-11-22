<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public Team $team, public string $fileId, public int $rows) {}

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
            ->subject('Your product export is ready')
            ->line("The export of {$this->rows} products from {$this->team->name} is ready.")
            ->action('Download', route('exports.download', $this->fileId))
            ->line('The file is kept for '.config('bandaros.keep_exports_for_days').' days.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'export_ready',
            'team_id' => $this->team->id,
            'title' => 'Your export is ready',
            'body' => "{$this->rows} products from {$this->team->name}. Click to download.",
            'url' => route('exports.download', $this->fileId, absolute: false),
        ];
    }
}
