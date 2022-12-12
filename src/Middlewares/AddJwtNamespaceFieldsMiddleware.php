<?php

namespace Carsguide\Auth\Middlewares;

use Auth0\SDK\Exception\Auth0Exception;
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Exception\InvalidTokenException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AddJwtNamespaceFieldsMiddleware extends BaseAuthMiddleware
{
    /**
     * Run the request filter.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param $fields
     * @return mixed
     * @throws Auth0Exception|CoreException
     */
    public function handle($request, Closure $next, $fields)
    {
        $this->request = $request;

        if (!$this->request->bearerToken()) {
            Log::info('No token provided');
            return $this->json('No token provided', 401);
        }

        //verify and decode token
        try {
            $this->verifyAndDecodeToken($this->request->bearerToken());
        } catch (InvalidTokenException $exception) {
            Log::info('Invalid token');
            return $this->json('Invalid token', 401);
        } catch (Auth0Exception|ConfigurationException $e) {
            Log::warning('Auth0 Exception', ['exceptionMessage' => $e->getMessage()]);
            return $this->json($e->getMessage(), 500);
        }

        $this->explodeFields($fields);

        $this->addFieldsToRequest();

        return $next($this->request);
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
            $namespaceField = $this->getJwtNamespace() . $field;

            $value =  $this->decodedToken[$namespaceField] ?? null;

            $input = ['jwt' => ['namespace' => [$field => $value]]];

            $this->request->merge($input);
        }
    }

    /**
     * Get JWT namespace
     *
     * @return string
     */
    protected function getJwtNamespace()
    {
        return env('AUTH0_JWT_NAMESPACE', 'https://platform.autotrader.com.au/');
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
