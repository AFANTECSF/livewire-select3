<?php

namespace JekHar\LivewireSelect2;

use Illuminate\Support\ServiceProvider;
use JekHar\LivewireSelect2\Components\Select2;
use Livewire\Livewire;

class LivewireSelect2ServiceProvider extends ServiceProvider
{
    public function boot()
    {

        Livewire::component('select2', Select2::class);

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/livewire-select2'),
        ], 'livewire-select2-views');

        $this->loadViewsFrom(__DIR__.'/resources/views', 'livewire-select2');
    }

    public function register()
    {
        $this->app->singleton('livewire-select2', function ($app) {
            return new Select2Manager();
        });
    }
}