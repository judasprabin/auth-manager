<?php

namespace Carsguide\Auth\Handlers;

use Psr\SimpleCache\CacheInterface;
use Illuminate\Support\Facades\Cache;

class CacheHandler implements CacheInterface
{
    /**
     * Retrieve decoded JWT from cache
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key);
    }

    /**
     * Empty cache for the provided key
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        Cache::forget($key);
    }

    /**
     * Put decoded JWT in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        // Cache item for 30 minutes
        Cache::put($key, $value, 30);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        // TODO: Implement clear() method.
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        // TODO: Implement getMultiple() method.
    }

    /**
     * @param iterable $values
     * @param int|\DateInterval|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        // TODO: Implement setMultiple() method.
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        // TODO: Implement deleteMultiple() method.
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        // TODO: Implement has() method.
    }
}
