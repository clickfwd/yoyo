<?php

namespace Clickfwd\Yoyo\Exceptions;

use Psr\Container\ContainerExceptionInterface;

class ContainerResolutionException extends \Exception implements ContainerExceptionInterface
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
