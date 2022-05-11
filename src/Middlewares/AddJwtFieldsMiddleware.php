<?php

namespace Carsguide\Auth\Middlewares;

use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Exception\InvalidTokenException;
use Closure;
use Illuminate\Http\JsonResponse;

class AddJwtFieldsMiddleware extends BaseAuthMiddleware
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
