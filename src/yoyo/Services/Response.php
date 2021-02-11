<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Response
{
    use Singleton;

    protected $headers = [];

    protected $status = 200;

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

        if ($this->status == 204) {
            http_response_code(204);
            return '';
        }

        http_response_code(200);

        return $content ?: '';
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
