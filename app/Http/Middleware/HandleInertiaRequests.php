<?php

namespace App\Http\Middleware;

use App\Models\ShippingMethod;
use App\Models\StoreSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = StoreSettings::current();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames() ?? [],
                'permissions' => $request->user()?->getAllPermissions()->pluck('name') ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'storeSettings' => [
                'currency' => $settings->currency,
                'currencyLocale' => $settings->currency_locale,
                'taxRate' => (float) $settings->tax_rate,
                'activeGateway' => $settings->active_payment_gateway,
                'stripePublicKey' => $settings->stripe_public_key,
            ],
            'shippingMethods' => fn () => ShippingMethod::active()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(['id', 'name', 'description', 'price', 'is_free']),
        ];
    }
}
