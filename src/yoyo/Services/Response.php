<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns;

class Response
{
    use Concerns\Singleton;
    use Concerns\ResponseHeaders;

    protected $headers = [];

    protected $statusCode = 200;

    public function __construct()
    {
    }

    public function header($name, $value)
    {
        // Sanitize header name and value to prevent header injection
        $name = str_replace(["\r", "\n", "\0"], '', (string) $name);
        $value = is_array($value) ? $value : str_replace(["\r", "\n", "\0"], '', (string) $value);

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
            if (is_array($value)) {
                $value = json_encode($value);
            }

            header("$key: $value");
        }

        // Prevent headers already sent error
        if (! headers_sent()) {
            http_response_code($this->statusCode ?? 200);
        }

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
