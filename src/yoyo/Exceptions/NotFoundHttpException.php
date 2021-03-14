<?php

namespace Clickfwd\Yoyo\Exceptions;

class NotFoundHttpException extends HttpException
{
    public function __construct(?string $message = '', array $headers = [])
    {
        parent::__construct(404, $message, $headers);
    }
}
