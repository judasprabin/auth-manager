<?php

namespace Carsguide\Auth\Handlers;

use Carsguide\Auth\Exceptions\InvalidArgumentException;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;

class CacheItemPoolHandler implements CacheItemPoolInterface
{
    private Repository $repository;

    /**
     * @var CacheItemInterface[]
     */
    private array $deferred = [];

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->commit();
    }

    /**
     * {@inheritdoc}
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            return clone $this->deferred[$key];
        } elseif ($this->repository->has($key)) {
            return new CacheItemHandler($key, unserialize($this->repository->get($key)), true);
        }

        return new CacheItemHandler($key);
    }

    /**
     * {@inheritdoc}
     *
     * @return iterable<string, CacheItemInterface>
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getItems(array $keys = []): iterable
    {
        return array_combine($keys, array_map(function ($key) {
            return $this->getItem($key);
        }, $keys));
    }

    /**
     * {@inheritdoc}
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function hasItem(string $key): bool
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            $item = $this->deferred[$key];
            $expiresAt = $this->getExpiresAt($item);

            if (!$expiresAt) {
                return true;
            }

            return $expiresAt > new DateTimeImmutable();
        }

        return $this->repository->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            $this->deferred = [];
            $this->repository->getStore()->flush();
        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);

        unset($this->deferred[$key]);

        if (!$this->hasItem($key)) {
            return true;
        }

        return $this->repository->forget($key);
    }

    /**
     * {@inheritdoc}
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteItems(array $keys): bool
    {
        // Validating all keys first.
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        $success = true;

        foreach ($keys as $key) {
            $success = $success && $this->deleteItem($key);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItemHandler) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItemHandler::class);
        }

        $expiresAt = $this->getExpiresAt($item);

        if (!$expiresAt) {
            try {
                $this->repository->forever($item->getKey(), serialize($item->get()));
            } catch (Throwable $exception) {
                return false;
            }

            return true;
        }

        $lifetime = static::computeLifetime($expiresAt);

        if ($lifetime <= 0) {
            $this->repository->forget($item->getKey());

            return false;
        }

        try {
            $this->repository->put($item->getKey(), serialize($item->get()), $lifetime);
        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItemHandler) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItemHandler::class);
        }

        $expiresAt = $this->getExpiresAt($item);

        if ($expiresAt && ($expiresAt < new DateTimeImmutable())) {
            return false;
        }

        $item = (new CacheItemHandler($item->getKey(), $item->get(), true))->expiresAt($expiresAt);

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function commit(): bool
    {
        $success = true;

        foreach ($this->deferred as $key => $item) {
            $success = $success && $this->save($item);
        }

        $this->deferred = [];

        return $success;
    }

    /**
     * @param string $key
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateKey(string $key): void
    {
        if (preg_match('#[{}\(\)/\\\\@:]#', $key)) {
            throw new InvalidArgumentException();
        }
    }

    /**
     * Calculate validity of the cache item
     *
     * @param DateTimeInterface $expiresAt
     * @return int
     * @throws \Exception
     */
    protected static function computeLifetime(DateTimeInterface $expiresAt): int
    {
        $now = new DateTimeImmutable('now', $expiresAt->getTimezone());

        return $expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * @param CacheItemHandler $item
     * @return DateTimeInterface|null
     */
    private function getExpiresAt(CacheItemHandler $item): ?DateTimeInterface
    {
        return $item->getExpiresAt();
    }
}
