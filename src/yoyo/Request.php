<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\RequestInterface;

class Request implements RequestInterface
{
    private $request;

    private $server;
    
    private $dropped = [];

    public function __construct()
    {
        $this->request = $_REQUEST;

        $this->server = $_SERVER;
    }

    public function mock($request, $server)
    {
        $this->request = $request;

        $this->server = $server;

        return $this;
    }

    public function reset()
    {
        $this->request = [];

        $this->server = [];
    }

    public function all()
    {
        return array_map(function ($value) {
            if ($decoded = YoyoHelpers::test_json($value)) {
                return $decoded;
            }

            return $value;
        }, $this->request);
    }

    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : [$keys];

        $all = $this->all();

        $output = [];

        foreach ($all as $key => $value) {
            if (in_array($key, $keys)) {
                continue;
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function only($keys)
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function get($key, $default = null)
    {
        if (in_array($key, $this->dropped)) {
            return $default;
        }

        $value = $this->request[$key] ?? $default;

        if ($decoded = YoyoHelpers::test_json($value)) {
            return $decoded;
        }

        return $value;
    }

    public function startsWith($prefix)
    {
        $vars = [];

        foreach ($this->all() as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    public function set($key, $value)
    {
        $this->request[$key] = $value;

        return $this;
    }
        
    public function merge($data)
    {
        $this->request = array_merge($this->request, $data);

        return $this;
    }
        
    public function drop($key)
    {
        $this->dropped[] = $key;
    }

    public function method()
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function fullUrl()
    {
        if (isset($this->server['HTTP_HX_CURRENT_URL'])) {
            return $this->server['HTTP_HX_CURRENT_URL'];
        }

        if (empty($this->server['HTTP_HOST'])) {
            return null;
        }

        $protocol = 'http';

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST'];

        $path = rtrim($_SERVER['REQUEST_URI'], '?');

        return "{$protocol}://{$host}{$path}";
    }

    public function isYoyoRequest()
    {
        return $this->server['HTTP_HX_REQUEST'] ?? false;
    }

    public function windUp()
    {
        unset($this->server['HTTP_HX_REQUEST']);
    }

    public function triggerId()
    {
        return $this->server['HTTP_HX_TRIGGER'];
    }

    public function triggerName()
    {
        return $this->server['HTTP_HX_TRIGGER_NAME'] ?? null;
    }
    public function header($name)
    {
        return $this->server['HTTP_'.strtoupper($name)] ?? null;
    }
}
