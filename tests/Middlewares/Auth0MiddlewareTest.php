<?php

namespace Carsguide\Tests\Middlewares;

use Carsguide\Auth\Middlewares\Auth0Middleware;
use Carsguide\Tests\TestCase;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

class Auth0MiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->registerServices();

        // putenv("AUTH0_AUDIENCE=");
        // putenv("AUTH0_DOMAIN=");
        // putenv("AUTH0_ALGORITHM=RS256");
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function requestWithoutAuthorizationHeaderWillNotProceed()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('hasHeader')->with('Authorization')->once();

        // Verify that Log::info() method has been called
        Log::shouldReceive('info')->once();

        $middleware = new Auth0Middleware();

        // The second parameter of the handle() method expecting a Closure.
        // Lumen usually pass next middleware as second parameter.
        $response = $middleware->handle($request, function () { }, 'inventory:post');

        $this->assertEquals('Authorization Header not found', $response->getOriginalContent());
        $this->assertEquals(401, $response->status());
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function verifyThatRequestDoesNotContainTheBearerToken()
    {
        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('hasHeader')
            ->with('Authorization')
            ->once()
            ->andReturn(true);

        $request->shouldReceive('header')
            ->with('Authorization')
            ->once()
            ->andReturn('xxxxx');

        $request->shouldReceive('bearerToken')
            ->once();

        // Verify that Log::info() method has been called
        Log::shouldReceive('info')->once();

        $middleware = new Auth0Middleware();

        // The second parameter of the handle() method expecting a Closure.
        // Lumen usually pass next middleware as second parameter.
        $response = $middleware->handle($request, function () { }, 'inventory:post');

        $this->assertEquals('No token provided', $response->getOriginalContent());

        $this->assertEquals(401, $response->status());
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function verifyThatRequestHasTheBearerToken()
    {
        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('hasHeader')
            ->with('Authorization')
            ->once()
            ->andReturn(true);

        $request->shouldReceive('header')
            ->with('Authorization')
            ->once()
            ->andReturn('xxxxx');

        $request->shouldReceive('bearerToken')
            ->andReturn('Bearer xxxxx');

        // Verify that Log::info() method has been called
        Log::shouldReceive('info')->once();

        $middleware = new Auth0Middleware();

        $response = $middleware->handle($request, function () { }, 'inventory:post');

        $this->assertEquals('Invalid token', $response->getOriginalContent());
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function itWillAcceptAValidToken()
    {
        $token = 'Bearer xxxxx';

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('hasHeader')
            ->with('Authorization')
            ->andReturn(true);

        $request->shouldReceive('header')
            ->with('Authorization')
            ->andReturn($token);

        $request->shouldReceive('bearerToken')
            ->andReturn($token);

        // Partially mock Auth0Middleware.
        // Only mocked verifyAndDecodeToken and VerifyUserHasScopeAccess methods
        // Rest of the methods will not be mocked and can be access as regular method.
        $middleware = Mockery::mock(Auth0Middleware::class)->makePartial();

        $middleware->shouldReceive('verifyAndDecodeToken')
            ->with($token)
            ->andReturn('jwt data');

        $middleware->shouldReceive('verifyUserHasScopeAccess')
            ->with('inventory:post')
            ->andReturn(true);

        $response = $middleware->handle($request, function () {
            return true;
        }, 'inventory:post');

        $this->assertTrue($response);
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function itWillNotAllowUnauthorizedScope()
    {
        $middleware = new Auth0Middleware();

        $middleware->decodedToken = (object)['Bearer token'];

        try {
            $middleware->verifyUserHasScopeAccess('inventory:post');
        } catch (Exception $e) {
            $this->assertEquals('No scopes defined in JWT', $e->getMessage());
        }

        $middleware->decodedToken->scope = 'inventory:get';

        try {
            $middleware->verifyUserHasScopeAccess('inventory:post');
        } catch (Exception $e) {
            $this->assertEquals('No access to scope', $e->getMessage());
        }
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function itWillExtractScopeFromToken()
    {
        $middleware = new Auth0Middleware();

        $middleware->decodedToken = (object)['scope' => 'inventory:post'];

        $this->assertTrue(in_array('inventory:post', $middleware->getScopesFromToken()));
    }

    /**
     * @test
     * @group auth0middleware
     */
    public function itWillThrowExceptionWhenNoScopedDefined()
    {
        $request = Mockery::mock('Illuminate\Http\Request');

        Log::shouldReceive('warning')->once();

        $middleware = Mockery::mock(Auth0Middleware::class)->makePartial();

        try {
            $middleware->handle($request, function () {
                return true;
            }, '');
        } catch (Exception $e) {
            $this->assertEquals('No scope defined for middleware', $e->getMessage());
        }
    }

    /**
     * This test is disabled until find a solution to use a fake token
     *
     * @group auth0middleware
     */
    public function verifyJwtCache()
    {
        $request = new Request();

        $request->headers->set('authorization', $this->getBearerToken());

        $middleware = new Auth0Middleware();

        Cache::shouldReceive('get')->once();
        Cache::shouldReceive('put')->once();

        $middleware->handle($request, function () { }, 'pricecalculator:post');

        $this->assertTrue(true);
    }

    protected function getBearerToken()
    {
        return '';
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
