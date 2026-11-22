<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a shop's owners and admins when a product's stock drops to the
 * low-stock level. Queued, so saving the product isn't slowed by email.
 */
class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stock = $this->product->stock;

        return (new MailMessage)
            ->subject("Low stock: {$this->product->name}")
            ->line($stock === 0
                ? "**{$this->product->name}** is out of stock."
                : "**{$this->product->name}** is running low: only {$stock} left.")
            ->action('View product', route('products.show', $this->product))
            ->line('You get this email because you manage this shop on '.config('app.name').'.');
    }

    /**
     * What the in-app notification list shows.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $stock = $this->product->stock;

        return [
            'type' => 'low_stock',
            'team_id' => $this->product->team_id,
            'title' => $stock === 0 ? "{$this->product->name} is out of stock" : "{$this->product->name} is running low",
            'body' => $stock === 0 ? 'Restock it to keep selling.' : "Only {$stock} left in stock.",
            'url' => route('products.show', $this->product, absolute: false),
        ];
    }
}
