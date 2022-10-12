<?php

namespace Carsguide\Auth\Middlewares;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Token;
use Psr\Cache\CacheItemPoolInterface;

class BaseAuthMiddleware
{
    /**
     * @var int $ttl
     */
    protected int $ttl = 1800;

    /**
     * verify and decode the token
     *
     * @param string $token
     * @return bool|void
     * @throws \Auth0\SDK\Exception\ConfigurationException
     * @throws \Auth0\SDK\Exception\CoreException
     */
    public function verifyAndDecodeToken($token)
    {
        $sdkConfiguration = new SdkConfiguration([
            'domain' =>  env('AUTH0_DOMAIN', false),
            'clientId' => env('AUTH0_JWT_CLIENTID'),
            'clientSecret' => env('AUTH0_JWT_CLIENTSECRET'),
            'audience' => [env('AUTH0_AUDIENCE', false)],
            'tokenAlgorithm' => env('AUTH0_ALGORITHM', 'RS256'),
            'strategy' => env('AUTH0_STRATEGY', 'api')
        ]);

        $tokenCache = app(CacheItemPoolInterface::class);

        $auth0 = new Auth0($sdkConfiguration);

        $auth0->configuration()->setTokenCache($tokenCache);
        $auth0->configuration()->setTokenCacheTtl($this->ttl);

        $decodedToken = $auth0->decode($token, null, null, null, null, null, null, Token::TYPE_TOKEN);

        $this->decodedToken = $decodedToken->toArray();
    }
}
