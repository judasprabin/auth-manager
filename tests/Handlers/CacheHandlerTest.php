<?php

namespace Carsguide\Tests\Handlers;

use Carsguide\Auth\Handlers\CacheHandler;
use Carsguide\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CacheHandlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->registerServices();
    }

    /**
     * @test
     * @group CacheHandler
     */
    public function setGetAndRemoveJwtFromCache()
    {
        $key = 'jwt-token';

        $cacheHandler = new CacheHandler();

        $cacheHandler->set($key, 'jwt token');

        $value = $cacheHandler->get($key);

        $this->assertEquals('jwt token', $value);

        $cacheHandler->delete($key);

        $this->assertNotEquals('jwt token', $cacheHandler->get($key));
    }
}
