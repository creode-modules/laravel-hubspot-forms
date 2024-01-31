<?php

namespace Creode\LaravelHubspotForms;

use Creode\LaravelHubspotForms\Http\View\HubspotForm;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class LaravelHubspotFormsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::component('hubspot-form', HubspotForm::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/components', 'laravel-hubspot-forms');

        $this->publishes([
            __DIR__.'/../config/laravel-hubspot-forms.php' => config_path('laravel-hubspot-forms.php'),
        ]);
    }
}
