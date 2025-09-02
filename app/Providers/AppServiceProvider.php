<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\People;
use App\Observers\PeopleObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        People::observe(PeopleObserver::class);
    }
}
