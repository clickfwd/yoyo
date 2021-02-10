<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Response
{
    use Singleton;

    private $headers = [];

    private $status = 200;

    public function header($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function status($code)
    {
        $this->status = $code;

        return $this;
    }

    public function send($content = ''): string
    {
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        http_response_code($this->status);

        return $content ?: '';
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
