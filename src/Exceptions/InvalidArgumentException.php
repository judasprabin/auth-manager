<?php

namespace Carsguide\Auth\Exceptions;

use Exception;
use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionInterface;

final class InvalidArgumentException extends Exception implements InvalidArgumentExceptionInterface
{
}
