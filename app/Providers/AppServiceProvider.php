<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\InventoryMovement;
use App\Observers\InventoryMovementObserver;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);

        // Super Admin bypass
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}

