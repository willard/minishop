<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

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
            ->subject("Low Stock Alert: {$this->product->name}")
            ->line("The product \"{$this->product->name}\" has reached a low stock level.")
            ->line("Current stock: {$this->product->stock_quantity} units.")
            ->line('SKU: '.($this->product->sku ?? 'N/A'))
            ->action('View Product', url(route('admin.products.show', $this->product)))
            ->line('Please restock this product soon.');
    }
}
