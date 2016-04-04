<?php

namespace Sako\Passport\Facades;

use Illuminate\Support\Facades\Facade;

class Passport extends Facade {

    /**
     * Return facade accessor
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'passport';
    }
}
