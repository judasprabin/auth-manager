<?php

namespace Carsguide\Auth\Exceptions;

use Exception;
use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionContract;

final class InvalidArgumentException extends Exception implements InvalidArgumentExceptionContract
{
}
