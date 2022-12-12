<?php

namespace Carsguide\Auth\Middlewares;

use Auth0\SDK\Exception\Auth0Exception;
use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\InvalidTokenException;
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
     * @throws Auth0Exception|ConfigurationException|CoreException
     */
    public function verifyAndDecodeToken(string $token)
    {
        $sdkConfiguration = new SdkConfiguration([
            'domain' =>  $this->getValidAuth0Domain($token),
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

    /**
     * Verify and retrieve valid Auth0 domain
     *
     * @param $token
     * @return string|null
     * @throws ConfigurationException
     * @throws InvalidTokenException
     */
    protected function getValidAuth0Domain($token): ?string
    {
        if (empty($token)) {
            return false;
        }

        $authorisedIssuer =  explode(',', env('AUTH0_DOMAIN', false));

        if (count(explode('.', $token))===1) {
            throw new InvalidTokenException('Invalid token');
        }

        $decodedToken = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))));

        if (empty($decodedToken->iss)) {
            throw new ConfigurationException('Issuer not found');
        }

        if (!in_array($decodedToken->iss, $authorisedIssuer)) {
            throw new ConfigurationException('We cannot trust on a token issued by `'.$decodedToken->iss.'`');
        }

        return $decodedToken->iss;
    }
}
