<?php

namespace Carsguide\Auth\Providers;

use Carsguide\Auth\AuthManager;
use EinarHansen\Cache\CacheItemPool;
use GuzzleHttp\Client;
use Illuminate\Cache\Repository;
use Illuminate\Support\ServiceProvider;
use Psr\Cache\CacheItemPoolInterface;

class AuthManagerServiceProvider extends ServiceProvider
{
    /**
     * Register auth service
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CacheItemPoolInterface::class, function ($app) {
            return new CacheItemPool($app->make(Repository::class));
        });

        $this->app->bind('authmanager', function () {
            return new AuthManager(new Client());
        });
    }
}
