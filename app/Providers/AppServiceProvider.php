<?php

namespace App\Providers;

use App\Models\Advisory;
use App\Models\Formalization10;
use Illuminate\Support\ServiceProvider;
use App\Models\People;
use App\Observers\AdvisoryObserver;
use App\Observers\Formalization10Observer;
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
        Advisory::observe(AdvisoryObserver::class);
        Formalization10::observe(Formalization10Observer::class);
    }
}
