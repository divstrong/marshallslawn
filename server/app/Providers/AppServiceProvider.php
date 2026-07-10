<?php

namespace App\Providers;

use App\Http\Responses\PanelAwareLoginResponse;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Property;
use App\Observers\InvoiceObserver;
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

        // Resolve the configured translation driver (issue #56).
        $this->app->singleton(\App\Services\Translation\TranslationDriver::class, function () {
            return match (config('services.translation.driver')) {
                'openai' => new \App\Services\Translation\OpenAiTranslationDriver(
                    config('services.translation.openai.key'),
                    config('services.translation.openai.model', 'gpt-4o-mini'),
                ),
                'google' => new \App\Services\Translation\GoogleTranslationDriver(
                    config('services.google.translate_key'),
                ),
                default => new \App\Services\Translation\NullTranslationDriver(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Property::observe(PropertyObserver::class);
        Job::observe(JobObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}
