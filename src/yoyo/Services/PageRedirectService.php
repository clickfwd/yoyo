<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class PageRedirectService
{
    use Singleton;

    private $response;

    public function __construct()
    {
        $this->response = Response::getInstance();
    }

    public function redirect($url)
    {
        if ($url) {
            $this->response->header('Yoyo-Redirect', $url);
        }
    }
}
