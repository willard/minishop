<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStoreSettingsRequest;
use App\Models\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingsController extends Controller
{
    public function edit(): Response
    {
        Gate::authorize('settings.view');

        $settings = StoreSettings::current();

        return Inertia::render('admin/Settings/Edit', [
            'settings' => [
                'currency' => $settings->currency,
                'currency_locale' => $settings->currency_locale,
                'tax_rate' => $settings->tax_rate,
                'tax_mode' => $settings->tax_mode?->value ?? 'flat_rate',
                'gst_number' => $settings->gst_number,
                'active_payment_gateway' => $settings->active_payment_gateway,
                'paymongo_public_key' => $settings->paymongo_public_key,
                'paymongo_secret_key' => $settings->paymongo_secret_key ? '••••••••' : null,
                'paymongo_webhook_secret' => $settings->paymongo_webhook_secret ? '••••••••' : null,
                'low_stock_threshold' => $settings->low_stock_threshold,
                'sale_discount_percentage' => $settings->sale_discount_percentage ?? 0,
                'origin_postcode' => $settings->origin_postcode,
            ],
            'hasPaymongoSecretKey' => ! empty($settings->paymongo_secret_key),
            'hasPaymongoWebhookSecret' => ! empty($settings->paymongo_webhook_secret),
        ]);
    }

    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('settings.update');

        $settings = StoreSettings::current();

        $data = $request->safe()->only([
            'currency',
            'currency_locale',
            'tax_rate',
            'tax_mode',
            'gst_number',
            'active_payment_gateway',
            'paymongo_public_key',
            'low_stock_threshold',
            'sale_discount_percentage',
            'origin_postcode',
        ]);

        // Only update secret keys if a new non-masked value is provided
        foreach (['paymongo_secret_key', 'paymongo_webhook_secret'] as $key) {
            $value = $request->input($key);
            if ($value && $value !== '••••••••') {
                $data[$key] = $value;
            }
        }

        $settings->update($data);

        return redirect()->route('admin.settings.edit')
            ->with('success', 'Store settings updated successfully.');
    }
}
