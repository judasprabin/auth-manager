<?php

namespace Carsguide\Auth\Middlewares;

use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\IdTokenVerifier;
use Carsguide\Auth\Handlers\CacheHandler;

class BaseAuthMiddleware
{
    /**
     * verify and decode the token
     *
     * @param string $token
     * @return bool|void
     */
    public function verifyAndDecodeToken($token)
    {
        $auth0Domain = $this->getAuth0Domain();

        $jwksUri = $auth0Domain . '.well-known/jwks.json';

        if (!$auth0Domain || filter_var($auth0Domain, FILTER_VALIDATE_URL) === false) {
            $jwksUri = '';
        }

        $jwksFetcher = new JWKFetcher(new CacheHandler(), [ 'base_uri' => $jwksUri ]);
        $jwks        = $jwksFetcher->getKeys();
        $sigVerifier = new AsymmetricVerifier($jwks);

        $idTokenVerifier = new IdTokenVerifier($auth0Domain, env('AUTH0_AUDIENCE', false), $sigVerifier);

        $this->decodedToken = $idTokenVerifier->verify($token);
    }

    /**
     * If multiple Auth0 domain is set, return the first one
     *
     * @return string
     */
    protected function getAuth0Domain(): string
    {
        $auth0Domains = explode(',', env('AUTH0_DOMAIN', false));

        return $auth0Domains[0] ?? '';
    }
}