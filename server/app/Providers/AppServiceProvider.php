<?php

namespace App\Providers;

use App\Http\Responses\PanelAwareLoginResponse;
use App\Models\Job;
use App\Models\Property;
use App\Observers\JobObserver;
use App\Observers\PropertyObserver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The admin and customer panels share a session; keep them from stealing
        // each other's post-login redirect out of `url.intended`.
        $this->app->bind(LoginResponse::class, PanelAwareLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Property::observe(PropertyObserver::class);
        Job::observe(JobObserver::class);
    }
}
