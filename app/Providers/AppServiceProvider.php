<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Models\TaxZone;
use App\Models\TaxZoneRate;
use App\Observers\CouponObserver;
use App\Observers\OrderObserver;
use App\Observers\OrderReturnObserver;
use App\Observers\ProductObserver;
use App\Observers\StoreSettingsObserver;
use App\Observers\TaxZoneObserver;
use App\Observers\TaxZoneRateObserver;
use App\Services\Shipping\CanadaPostCarrier;
use App\Services\Shipping\ShippingRateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();
        $this->registerGates();
        $this->registerShippingService();
    }

    protected function registerShippingService(): void
    {
        $this->app->singleton(ShippingRateService::class, function (): ShippingRateService {
            $service = new ShippingRateService;

            if (config('services.canada_post.username') && config('services.canada_post.customer_number')) {
                $service->registerDriver(new CanadaPostCarrier);
            }

            return $service;
        });
    }

    protected function registerObservers(): void
    {
        Order::observe(OrderObserver::class);
        OrderReturn::observe(OrderReturnObserver::class);
        Product::observe(ProductObserver::class);
        Coupon::observe(CouponObserver::class);
        TaxZone::observe(TaxZoneObserver::class);
        TaxZoneRate::observe(TaxZoneRateObserver::class);
        StoreSettings::observe(StoreSettingsObserver::class);
    }

    protected function registerGates(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
