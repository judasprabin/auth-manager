<?php

namespace Carsguide\Auth;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

class AuthManager
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

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
     * Get Auth0 token
     *
     * @return \StdClass
     */
    public function getToken()
    {
        try {
            $response = $this->client->post($this->getUrl(), ['json' => $this->getOptions()]);

            return $this->successResponse($response);

        } catch (RequestException $e) {
            return $this->errorResponse($e->getResponse());
        }
    }

    /**
     * Prepare successful response with data
     *
     * @param \GuzzleHttp\Psr7\Response  $respone
     * @return \StdClass
     */
    public function successResponse(Response $response)
    {
        return (object) [
            'success' => true,
            'status_code' => $response->getStatusCode(),
            'access_token' => ($this->decodeResponse($response))->access_token,
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
            'audience' => env('AUTH0_AUDIENCE'),
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
