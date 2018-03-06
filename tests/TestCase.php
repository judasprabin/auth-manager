<?php

namespace Carsguide\Tests;

use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var \Illuminate\Container\Container $container
     */
    protected $container;

    /**
     * Register services to IoC container
     *
     * @return void
     */
    protected function registerServices()
    {
        // For standalone package, laravel container does not exist.
        // And cache manager uses container to get the settings.
        // Get the container instance.
        $this->container = Container::getInstance();

        // Lumen does not exist. Basic setup require to bootstrap cache.
        // For testing, we only using array driver.
        // The CacheManager creates the cache "repository" based on config values
        // which are loaded from the config class in the container.
        $this->container['config'] = [
            'cache.default' => 'array',
            'cache.stores.array' => [
                'driver' => 'array',
            ],
        ];

        // Bind Services to IoC container
        $this->bindServicesToContainer();

        // Register Cache and Log facades
        $this->registerFacades();
    }

    /**
     * Register Facades
     *
     * @return void
     */
    protected function registerFacades()
    {
        // Register the facades
        // Set the application container
        // The Facade's underlying class will resolve via this container
        Facade::setFacadeApplication($this->container);

        if (!class_exists('Cache')) {
            class_alias(Cache::class, 'Cache');
        }

        if (!class_exists('Log')) {
            class_alias(Log::class, 'Log');
        }
    }

    /**
     * Bind Services to container
     *
     * @return void
     */
    protected function bindServicesToContainer()
    {
        $this->container->singleton('cache', function ($container) {
            return new CacheManager($container);
        });

        $this->container->singleton('cache.store', function ($container) {
            return $container['cache']->driver();
        });

        $this->container->instance('Psr\Log\LoggerInterface', $this->getMonologInstance());

        $this->container->singleton('log', function ($container) {
            return new LogManager($container);
        });

    }

    /**
     * Create a custom Monolog instance.
     *
     * @return \Monolog\Logger
     */
    protected function getMonologInstance()
    {
        /* Use logstash formatter for logstash log file */
        $stream = (new StreamHandler('./logs/logstash.log', 'INFO'))
            ->setFormatter(new LogstashFormatter('lumen', null, null, 'ctxt_', 1));

        return (new Logger('custom'))->pushHandler($stream);
    }
}
