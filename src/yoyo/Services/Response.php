<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Response
{
    use Singleton;

    protected $headers = [];

    protected $statusCode = 200;

    public function header($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function status($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function send(string $content = ''): string
    {
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        if ($this->statusCode == 204) {
            http_response_code(204);
        }

        http_response_code($this->statusCode ?? 200);

        return $content ?: '';
    }

    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }    
}
