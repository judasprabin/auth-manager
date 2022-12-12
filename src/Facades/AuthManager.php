<?php

namespace Carsguide\Auth\Facades;

use Illuminate\Support\Facades\Facade;

class AuthManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'authmanager';
    }
}
