<?php

namespace Carsguide\Auth;

use Exception;
use GuzzleHttp\Client;
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
     * Client Id
     *
     * @var string
     */
    protected $clientId = null;

    /**
     * Client secret
     *
     * @var string
     */
    protected $clientSecret = null;

    /**
     * url
     *
     * @var string
     */
    protected $url = null;

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
     * @param \GuzzleHttp\Client $client
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
     * Set client id.
     *
     * @param string $clientId
     * @return $this
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get the client Id
     *
     * @return string
     */
    public function getClientId()
    {
        if (!$this->clientId) {
            $this->clientId = env('AUTH0_JWT_CLIENTID');
        }

        return $this->clientId;
    }

    /**
     * Set client secret.
     *
     * @param string $clientSecret
     * @return $this
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * Get the client secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        if (!$this->clientSecret) {
            $this->clientSecret = env('AUTH0_JWT_CLIENTSECRET');
        }

        return $this->clientSecret;
    }

    /**
     * Cache JWT token
     *
     * @param int $time
     * @return $this
     */
    public function cache(int $time = 3000)
    {
        $this->cache = true;
        $this->cacheTime = $time;

        return $this;
    }

    /**
     * Get cache key
     *
     * @return string
     * @throws Exception
     */
    public function cacheKey()
    {
        return 'auth0_jwt_' . $this->getAudience();
    }

    /**
     * Get Auth0 token
     * https://www.oauth.com/oauth2-servers/access-tokens/access-token-response/
     * @return \StdClass
     * @throws Exception
     */
    public function getToken()
    {
        if ($this->cache && Cache::has($this->cacheKey())) {
            return $this->successResponse(200, Cache::get($this->cacheKey()));
        }

        try {
            $response = $this->client->post($this->getUrl(), ['json' => $this->getOptions()]);

            if ($response->getStatusCode() != 200) {
                throw new Exception(($this->decodeResponse($response))->error_description);
            }

            $accessToken = ($this->decodeResponse($response))->access_token;

            if ($this->cache) {
                Cache::put($this->cacheKey(), $accessToken, $this->cacheTime);
            }

            return $this->successResponse($response->getStatusCode(), $accessToken);

        } catch (Exception $e) {
            return $this->errorResponse($e->getCode(), $e->getMessage());
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
        return (object)[
            'success' => true,
            'status_code' => $code,
            'access_token' => $accessToken,
        ];
    }

    /**
     * Prepare error response
     *
     * @param int code
     * @param string message
     * @return \StdClass
     */
    public function errorResponse($code, $message)
    {
        return (object)[
            'success' => false,
            'status_code' => $code,
            'message' => "AuthManager exception: " . $message,
        ];
    }

    /**
     * Decode GuzzleHttp response
     *
     * @param \GuzzleHttp\Psr7\Response $response
     * @return mixed
     */
    public function decodeResponse(Response $response)
    {
        return json_decode((string)$response->getBody());
    }

    /**
     * Get Auth0 config
     *
     * @return array
     */
    public function getOptions()
    {
        $options = [
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
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
     * Set url.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get Auth0 OAuth URL
     *
     * @return string
     */
    public function getUrl()
    {
        If (!$this->url) {
            if (!$this->url = env('AUTH0_OAUTH_URL')) {
                throw new Exception("Auth0 OAuth URL not set");
            }
        }

        return $this->url;
    }
}
