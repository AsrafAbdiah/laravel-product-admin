<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportFinishedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{imported: int, failed: array<int, array{row: int, errors: array<int, string>}>}  $result
     */
    public function __construct(public Team $team, public string $fileName, public array $result) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Import finished: {$this->fileName}")
            ->line($this->summary());

        foreach (array_slice($this->result['failed'], 0, 10) as $failure) {
            $message->line("Row {$failure['row']}: ".implode(' ', $failure['errors']));
        }

        return $message->action('View products', route('products.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'import_finished',
            'team_id' => $this->team->id,
            'title' => "Import of {$this->fileName} finished",
            'body' => $this->summary(),
            // Keep the first problems so they can be fixed without the email.
            'failed' => array_slice($this->result['failed'], 0, 20),
            'url' => route('products.index', absolute: false),
        ];
    }

    private function summary(): string
    {
        $skipped = count($this->result['failed']);

        return "{$this->result['imported']} products imported".($skipped ? ", {$skipped} rows skipped." : '.');
    }
}
