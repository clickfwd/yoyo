<?php

namespace Clickfwd\Yoyo\Exceptions;

class HttpException extends \Exception
{
    public function __construct(int $statusCode, ?string $message = '', array $headers = [])
    {
        $this->statusCode = $statusCode;

        $this->headers = $headers;

        parent::__construct($message, $statusCode);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }    
}
