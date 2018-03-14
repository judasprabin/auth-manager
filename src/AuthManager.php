<?php

namespace Carsguide\Auth;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

class AuthManager
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Api audience
     *
     * @var string
     */
    protected $audience;

    /**
     * Determine JWT is cached or not.
     *
     * @var string
     */
    protected $cache = false;

    /**
     * Cache duration
     *
     * @var string
     */
    protected $cacheTime;

    /**
     * Constructor
     *
     * @param \GuzzleHttp\Client  $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set api audience.
     *
     * @param string $audience
     * @return $this
     */
    public function setAudience($audience)
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * Get the audience
     *
     * @return string
     */
    public function getAudience()
    {
        if (!$this->audience) {
            throw new Exception("Audience is not set.", 1);
        }

        return $this->audience;
    }

    /**
     * Cache JWT token
     *
     * @param int $time
     * @return $this
     */
    public function cache(int $time = 50)
    {
        $this->cache = true;
        $this->cacheTime = $time;

        return $this;
    }

    /**
     * Get cache key
     *
     * @return string
     */
    public function cacheKey()
    {
        return 'auth0_jwt_' . $this->getAudience();
    }

    /**
     * Get Auth0 token
     *
     * @return \StdClass
     */
    public function getToken()
    {
        if ($this->cache && Cache::has($this->cacheKey())) {
            return $this->successResponse(200, Cache::get($this->cacheKey()));
        }

        try {
            $response = $this->client->post($this->getUrl(), ['json' => $this->getOptions()]);

            $accessToken = ($this->decodeResponse($response))->access_token;

            if ($this->cache) {
                Cache::put($this->cacheKey(), $accessToken, $this->cacheTime);
            }

            return $this->successResponse($response->getStatusCode(), $accessToken);

        } catch (RequestException $e) {
            return $this->errorResponse($e->getResponse());
        }
    }

    /**
     * Prepare successful response with data
     *
     * @param int $code
     * @param string $accessToken
     * @return \StdClass
     */
    public function successResponse($code, $accessToken)
    {
        return (object) [
            'success' => true,
            'status_code' => $code,
            'access_token' => $accessToken,
        ];
    }

    /**
     * Prepare error response
     *
     * @param \GuzzleHttp\Psr7\Response  $respone
     * @return \StdClass
     */
    public function errorResponse(Response $response)
    {
        $body = $this->decodeResponse($response);

        return (object) [
            'success' => false,
            'status_code' => $response->getStatusCode(),
            'message' => isset($body->error) ? $body->error : 'Auth0 Exception caught',
        ];
    }

    /**
     * Decode GuzzleHttp response
     *
     * @param \GuzzleHttp\Psr7\Response  $response
     * @return mixed
     */
    public function decodeResponse(Response $response)
    {
        return json_decode((string) $response->getBody());
    }

    /**
     * Get Auth0 config
     *
     * @return array
     */
    public function getOptions()
    {
        $options = [
            'client_id' => env('AUTH0_JWT_CLIENTID'),
            'client_secret' => env('AUTH0_JWT_CLIENTSECRET'),
            'audience' => $this->getAudience(),
            'grant_type' => env('AUTH0_GRANT_TYPE', 'client_credentials'),
        ];

        foreach ($options as $key => $option) {
            if (!$option) {
                throw new Exception("Auth0 {$key} not set");
            }
        }

        return $options;
    }

    /**
     * Get Auth0 OAuth URL
     *
     * @return string
     */
    public function getUrl()
    {
        if (!$url = env('AUTH0_OAUTH_URL')) {
            throw new Exception("Auth0 OAuth URL not set");
        }

        return $url;
    }
}
