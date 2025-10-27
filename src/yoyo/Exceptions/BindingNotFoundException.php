<?php

namespace Clickfwd\Yoyo\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class BindingNotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
