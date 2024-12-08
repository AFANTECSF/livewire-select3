<?php

namespace afantecsf\LivewireSelect3;

use Illuminate\Support\ServiceProvider;
use afantecsf\LivewireSelect3\Components\Select3;
use Livewire\Livewire;

class LivewireSelect3ServiceProvider extends ServiceProvider
{
    public function boot()
    {

        Livewire::component('select3', Select3::class);

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/livewire-select3'),
        ], 'livewire-select3-views');

        $this->loadViewsFrom(__DIR__.'/resources/views', 'livewire-select3');
    }

    public function register()
    {
        $this->app->singleton('livewire-select3', function ($app) {
            return new Select3Manager();
        });
    }
}