<?php

namespace JekHar\LivewireSelect2\Facades;

use Illuminate\Support\Facades\Facade;

class LivewireSelect2 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'livewire-select2';
    }
}