<?php

namespace Carsguide\Auth\Handlers;

use Auth0\SDK\Helpers\Cache\CacheHandler as CacheInterface;
use Illuminate\Support\Facades\Cache;

class CacheHandler implements CacheInterface
{
    /**
     * Retrieve decoded JWT from cache
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return Cache::get($key);
    }

    /**
     * Empty cache for the provided key
     *
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        Cache::forget($key);
    }

    /**
     * Put decoded JWT in cache
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        // Cache item for 30 minutes
        Cache::put($key, $value, 30);
    }
}
