# auth-manager
Manages Lumen and Laravel Auth0 integration in microservices using PHP 8.0 version.

## Installation
Via composer
```
$ composer require carsguide/auth-manager
```

## Environment settings .env file
```
AUTH0_AUDIENCE=
AUTH0_OAUTH_URL=
AUTH0_DOMAIN=
AUTH0_JWT_CLIENTID=
AUTH0_JWT_CLIENTSECRET=
AUTH0_ALGORITHM=
```

| Value         | What it is    |
| ------------- |-------------|
| AUTH0_AUDIENCE  | Auth0 audience/identifier of the API micro service verifying the token |
| AUTH0_OAUTH_URL | Auth0 URL to query to get a token from (the tenant) |
| AUTH0_DOMAIN | Auth0 domain of tenant (used during token verifcation) |
| AUTH0_JWT_CLIENTID | Auth0 client ID of the micro service getting a token |
| AUTH0_JWT_CLIENTSECRET | Auth0 client secret of the micro service getting a token |
| AUTH0_ALGORITHM | Algorithm method, advise RS256 (default) |

### Registering service provider
####Lumen
Add the following snippet to the `bootstrap/app.php` file under the register service providers section:

```php
$app->register(Carsguide\Auth\Providers\AuthManagerServiceProvider::class);
```

####Laravel
Add the following snippet to the `config/app.php` file under the register service providers section:

```php
Carsguide\Auth\Providers\AuthManagerServiceProvider::class,
```

### Registering middleware
To use token and scope validation register the middleware via routeMiddleware()

####Lumen: in bootstrap/app.php

```php
$app->routeMiddleware([
    'auth' => Carsguide\Auth\Middlewares\Auth0Middleware::class,
]);
```

####Laravel: app/Http/kernel.php

```php
protected $routeMiddleware = [
    'auth' => \Carsguide\Auth\Middlewares\Auth0Middleware::class,
];
````

## Usage
### Generate JWT Token
```php
use Carsguide\Auth\AuthManager;
use GuzzleHttp\Client;

$auth = new AuthManager(new Client());
$auth = $auth->setAudience('foobar');
$auth->getToken();
```

Using `AuthManager` Facade:

```php
use Carsguide\Auth\Facades\AuthManager;

AuthManager::setAudience('foobar')->getToken();
```

Cache JWT token:
```php
 AuthManager::setAudience('foobar')
    // By default, JWT will cache for 50 minutes
    // If you need to override the default length, 
    // pass minutes in cache(120) method.
    ->cache() // or ->cache($minutes = 120)
    ->getToken();
```

### Validate JWT Token / Scope Access
Each token is validated via middleware.  You must call the middleware in routes or the controller to validate access.  The middleware requires a scope be defined, nothing can be global.

```php
$this->middleware('auth:listings:read');
```

Using routes file

```php
$router->get('admin/profile', ['middleware' => 'auth:listings:read', function () {
    //
}]);
```
