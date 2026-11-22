<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklySummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{products: int, enabled: int, disabled: int, low_stock: int, in_trash: int, inventory_value: string}  $totals
     * @param  array<int, array{name: string, stock: int}>  $lowStock
     */
    public function __construct(public Team $team, public array $totals, public array $lowStock) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Your weekly stock summary: {$this->team->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Here's where **{$this->team->name}** stands this week:")
            ->line("- Products: {$this->totals['products']} ({$this->totals['enabled']} enabled)")
            ->line('- Inventory value: RM '.number_format((float) $this->totals['inventory_value'], 2))
            ->line("- Running low: {$this->totals['low_stock']}");

        if ($this->lowStock) {
            $message->line('**Restock soon:**');
            foreach ($this->lowStock as $product) {
                $message->line("- {$product['name']}: ".($product['stock'] === 0 ? 'out of stock' : "{$product['stock']} left"));
            }
        }

        return $message->action('Open dashboard', route('dashboard'));
    }
}
