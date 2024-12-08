<?php

namespace afantecsf\LivewireSelect3\Facades;

use Illuminate\Support\Facades\Facade;

class LivewireSelect3 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'livewire-select3';
    }
}