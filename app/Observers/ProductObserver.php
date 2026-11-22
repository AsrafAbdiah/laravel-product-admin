<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Notifications\LowStockNotification;
use App\Support\Roles;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Notification;

/**
 * Records a stock movement whenever a product's stock changes, wherever
 * the change comes from (API, dashboard or import). Doing it in one
 * observer means no code path can forget to log it.
 *
 * Callers can label the change by setting "stock_reason" in Laravel's
 * Context (the import does this), otherwise the event name is used.
 */
class ProductObserver
{
    public function created(Product $product): void
    {
        $this->record($product, 0, Context::get('stock_reason', StockMovement::REASON_CREATED));
    }

    public function updated(Product $product): void
    {
        if (! $product->wasChanged('stock')) {
            return;
        }

        // Inside "updated", getOriginal() still holds the value from before the save.
        $oldStock = (int) $product->getOriginal('stock');
        $this->record($product, $oldStock, Context::get('stock_reason', StockMovement::REASON_UPDATED));

        // Alert only when stock *crosses* into low stock, not on every change below it.
        if ($oldStock > Product::LOW_STOCK_THRESHOLD && $product->stock <= Product::LOW_STOCK_THRESHOLD) {
            Notification::send(
                $product->team->membersWithRole([Roles::OWNER, Roles::ADMIN]),
                new LowStockNotification($product),
            );
        }
    }

    private function record(Product $product, int $oldStock, string $reason): void
    {
        $product->stockMovements()->create([
            // Always the product's own team, even when no team is set (seeders, queues).
            'team_id' => $product->team_id,
            'user_id' => auth()->id(),
            'old_stock' => $oldStock,
            'new_stock' => $product->stock,
            'change' => $product->stock - $oldStock,
            'reason' => $reason,
        ]);
    }
}
