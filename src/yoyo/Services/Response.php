<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Response
{
    use Singleton;

    private $headers = [];

    public function header($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function send($content = '')
    {
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        return $content;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
