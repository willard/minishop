<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSettings extends Model
{
    protected $fillable = [
        'currency',
        'currency_locale',
        'tax_rate',
        'active_payment_gateway',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paymongo_public_key',
        'paymongo_secret_key',
        'paymongo_webhook_secret',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'stripe_secret_key' => 'encrypted',
            'stripe_webhook_secret' => 'encrypted',
            'paymongo_secret_key' => 'encrypted',
            'paymongo_webhook_secret' => 'encrypted',
            'tax_rate' => 'decimal:2',
            'low_stock_threshold' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
}
