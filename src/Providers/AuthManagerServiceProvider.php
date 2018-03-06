<?php

namespace Carsguide\Auth\Providers;

use Carsguide\Auth\AuthManager;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AuthManagerServiceProvider extends ServiceProvider
{
    /**
     * Register auth service
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('authmanager', function () {
            return new AuthManager(new Client());
        });
    }
}
