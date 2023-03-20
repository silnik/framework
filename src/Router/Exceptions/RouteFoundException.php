<?php

declare(strict_types=1);

namespace Silnik\Router\Exceptions;

class RouteFoundException extends \Exception
{
    protected $message = '404 Not Found';
}