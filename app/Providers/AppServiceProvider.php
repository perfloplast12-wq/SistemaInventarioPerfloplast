<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\InventoryMovement;
use App\Models\Production;
use App\Observers\InventoryMovementObserver;
use App\Observers\ProductionObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
    }

    public function boot(): void
    {
        // Force period decimals and 2 decimal places globally for Number helper
        Number::useLocale('en_US');
        
        // Force native PHP number_format to use period decimals
        setlocale(LC_NUMERIC, 'en_US.UTF-8');

        User::observe(UserObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
        Production::observe(ProductionObserver::class);

        // Super Admin bypass
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}

