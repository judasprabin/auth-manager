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
use Illuminate\Support\Facades\Log;

class Auth0Middleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure $next
     * @param  string $scope
     * @return mixed
     */
    public function handle($request, Closure $next, $scope)
    {
        //no scope defined
        if (empty($scope)) {
            Log::warning('No scope defined for middleware');
            throw new Exception('No scope defined for middleware');
        }

        //no auth header
        if (!$request->hasHeader('Authorization')) {
            Log::info('Authorization Header not found');
            return $this->json('Authorization Header not found', 401);
        }

        //auth header sent but is null
        if ($request->header('Authorization') == null || $request->bearerToken() == null) {
            Log::info('No token provided');
            return $this->json('No token provided', 401);
        }

        //verify and decode token
        try {
            $this->verifyAndDecodeToken($request->bearerToken());
        } catch (InvalidTokenException $e) {
            Log::info('Invalid token');
            return $this->json('Invalid token', 401);
        } catch (CoreException $e) {
            Log::warning('Auth0 Core Exception', ['exceptionMessage' => $e->getMessage()]);
            return $this->json($e->getMessage(), 401);
        }

        //does user have access to the scope
        try {
            $this->verifyUserHasScopeAccess($scope);
        } catch (Exception $e) {
            Log::warning('Scope access denied', ['exceptionMessage' => $e->getMessage()]);
            return $this->json($e->getMessage(), 403);
        }

        //all ok, proceed
        return $next($request);
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
     * verify the user has access to scope and scopes are defined in JWT
     *
     * @param string $scope
     * @throws Exception
     * @return void
     */
    public function verifyUserHasScopeAccess($scope)
    {
        //no scopes defined
        if (empty($this->decodedToken['scope'])) {
            throw new Exception('No scopes defined in JWT');
        }

        //if requested scope is not in JWT scopes, user has no access
        if (!in_array($scope, $this->getScopesFromToken())) {
            throw new Exception('No access to scope');
        }
    }

    /**
     * Turn scopes string into array
     *
     * @return array
     */
    public function getScopesFromToken()
    {
        return explode(' ', $this->decodedToken['scope']);
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
