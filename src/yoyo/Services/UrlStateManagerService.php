<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Yoyo;

class UrlStateManagerService
{
    private $request;

    private $currentUrl;

    public function __construct()
    {
        $this->request = Yoyo::request();

        $this->currentUrl = $this->request->fullUrl();
    }

    public function pushState($queryParams)
    {
        $response = Response::getInstance();

        if (! $this->currentUrl || $this->request->method() !== 'GET') {
            return;
        }

        $parsedUrl = parse_url($this->currentUrl);

        $url = $parsedUrl['scheme'].'://'.$parsedUrl['host'].$parsedUrl['path'].($queryParams ? '?'.http_build_query($queryParams) : '');

        if ($url !== $this->currentUrl) {
            $response->header('HX-Push', $url);
        }
    }
}
