<?php

namespace Carsguide\Auth\Middlewares;

use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Exception\InvalidTokenException;
use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\IdTokenVerifier;
use Carsguide\Auth\Handlers\CacheHandler;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;

class AddJwtFieldsMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure $next
     * @param  string $scope
     * @return mixed
     */
    public function handle($request, Closure $next, $fields)
    {
        $this->request = $request;

        //verify and decode token
        try {
            $this->verifyAndDecodeToken($this->request->bearerToken());
        } catch (CoreException | InvalidTokenException $e) {
            //Log::info('Invalid token');
            //return $this->json('Invalid token', 401);
        }

        $this->explodeFields($fields);

        $this->addFieldsToRequest();

        return $next($this->request);
    }

    /**
     * verify and decode the token
     *
     * @param string $token
     * @return bool|void
     */
    public function verifyAndDecodeToken($token)
    {
        $auth0Domain = $this->getAuth0Domain();

        if (!$auth0Domain || filter_var($auth0Domain, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $jwksUri = $auth0Domain . '.well-known/jwks.json';

        $jwksFetcher = new JWKFetcher(new CacheHandler(), [ 'base_uri' => $jwksUri ]);
        $jwks        = $jwksFetcher->getKeys();
        $sigVerifier = new AsymmetricVerifier($jwks);

        $idTokenVerifier = new IdTokenVerifier($auth0Domain, env('AUTH0_AUDIENCE', false), $sigVerifier);

        $this->decodedToken = $idTokenVerifier->verify($token);
    }

    /**
     * Explode fields
     *
     * @param $fields
     * @return void
     */
    public function explodeFields($fields)
    {
        $this->fields = explode(':', $fields);
    }

    /**
     * Explode fields
     *
     * @return void
     */
    public function addFieldsToRequest()
    {
        foreach ($this->fields as $field) {
            $value = $this->decodedToken[$field] ?? null;
            $this->request->merge([$field => $value]);
        }
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

    /**
     * Return a new JSON response from the application.
     *
     * @param  string|array  $data
     * @param  int    $status
     * @param  array  $headers
     * @param  int    $options
     * @return \Illuminate\Http\JsonResponse;
     */
    protected function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }
}
