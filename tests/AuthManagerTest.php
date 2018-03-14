<?php

namespace Carsguide\Tests;

use Carsguide\Auth\AuthManager;
use Carsguide\Tests\TestCase;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class AuthManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        putenv("AUTH0_OAUTH_URL=https://auth0.example");
        putenv("AUTH0_JWT_CLIENTID=clientId");
        putenv("AUTH0_JWT_CLIENTSECRET=secret");
    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillReturnAuth0AccessToken()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'JWT token'])),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $auth = (new AuthManager($client))->setAudience('foo')->getToken();

        $this->assertEquals('JWT token', $auth->access_token);
        $this->assertEquals(200, $auth->status_code);
    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillCatchRequestException()
    {
        $mock = new MockHandler([
            new RequestException("Access denied", new Request('post', 'https://auth0.test'), new Response(401, [], json_encode(['error' => 'access_denied']))),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $auth = (new AuthManager($client))->setAudience('foo')->getToken();

        $this->assertEquals('access_denied', $auth->message);
        $this->assertEquals(401, $auth->status_code);
    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillReturnValidSuccessResponse()
    {
        $auth = (new AuthManager(new Client))->setAudience('foo');

        $response = new Response(200, [], json_encode(['access_token' => 'JWT token']));

        $auth = $auth->successResponse($response->getStatusCode(), ($auth->decodeResponse($response))->access_token);

        $this->assertEquals('JWT token', $auth->access_token);
        $this->assertEquals(200, $auth->status_code);
    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillReturnValidErrorResponse()
    {
        $auth = (new AuthManager(new Client))->setAudience('foo');

        $response = new Response(401, [], json_encode(['error' => 'access_denied']));

        $auth = $auth->errorResponse($response);

        $this->assertEquals('access_denied', $auth->message);
        $this->assertEquals(401, $auth->status_code);
    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillDecodeResponse()
    {
        $auth = (new AuthManager(new Client))->setAudience('foo');

        $response = new Response(200, [], json_encode(['access_token' => 'JWT token']));

        $auth = $auth->decodeResponse($response);

        $this->assertEquals('JWT token', $auth->access_token);

    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillThrowExceptionIfAuth0ConfigIsNotSet()
    {
        $this->expectException(Exception::class);

        putenv("AUTH0_JWT_CLIENTID=");

        $auth = new AuthManager(new Client);

        $auth = $auth->getOptions();
    }

    /**
     * @test
     * @group AuthManager
     */
    public function itWillThrowExceptionIfAuth0OauthUrlIsNotSet()
    {
        $this->expectException(Exception::class);

        putenv("AUTH0_OAUTH_URL=");

        $auth = new AuthManager(new Client);

        $auth = $auth->getUrl();
    }

    /**
     * @test
     * @group AuthManager
     */
    public function shouldThrowExceptionIfAudienceIsNotSet()
    {
        $this->expectException(Exception::class);

        $auth = new AuthManager(new Client);

        $auth->getToken();
    }

    /**
     * @test
     * @group AuthManager
     */
    public function shouldReturnAudience()
    {
        $auth = (new AuthManager(new Client))->setAudience('foo');

        $this->assertEquals('foo', $auth->getAudience());
    }
}
