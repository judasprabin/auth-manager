<?php

namespace Carsguide\Auth\Handlers;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

class CacheItemHandler implements CacheItemInterface
{
    /**
     * @var string
     */
    private string $key;

    /**
     * @var mixed|null
     */
    private mixed $value;

    /**
     * @var bool
     */
    private bool $hit;

    /**
     * @var DateTimeInterface|null
     */
    private ?DateTimeInterface $expires = null;

    /**
     * @param string $key
     * @param mixed|null $value
     * @param bool $hit
     */
    public function __construct(string $key, mixed $value = null, bool $hit = false)
    {
        $this->key = $key;
        $this->hit = $hit;
        $this->value = $this->hit ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * {@inheritdoc}
     */
    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        if ($expiration instanceof DateTimeInterface && !$expiration instanceof DateTimeImmutable) {
            $timezone = $expiration->getTimezone();
            $expiration = DateTimeImmutable::createFromFormat('U', (string) $expiration->getTimestamp(), $timezone);

            if ($expiration) {
                $expiration = $expiration->setTimezone($timezone);
            }
        }

        if ($expiration instanceof DateTimeInterface) {
            $this->expires = $expiration;
        } else {
            $this->expires = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expires = null;

            return $this;
        }

        $this->expires = new DateTimeImmutable();

        if (! $time instanceof DateInterval) {
            $time = new DateInterval(sprintf('PT%sS', $time));
        }

        $this->expires = $this->expires->add($time);

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expires;
    }
}
