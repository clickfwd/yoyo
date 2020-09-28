<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Services\Request;

class QueryString
{
    private $defaults;

    private $new;

    private $keys;

    private $currentUrl;

    public function __construct($defaults, $new, $keys)
    {
        $this->defaults = $defaults;

        $this->new = $new;

        $this->keys = $keys;

        $this->currentUrl = (Request::getInstance())->fullUrl();
    }

    /**
     * Used to pass variables to the component request.
     */
    public function getQueryParams()
    {
        $queryParams = array_merge($this->defaults, $this->new);

        // Filter out keys that are not explicitly set in the component queryString property

        $queryParams = array_intersect_key($queryParams, array_flip($this->keys));

        $queryParams = array_filter($queryParams);

        return $queryParams;
    }

    /**
     * Used to update the browser URL state.
     */
    public function getPageQueryParams()
    {
        $currentUrl = $this->currentUrl;

        // Filter out keys that are not explicitly set in the component queryString property

        $new = array_intersect_key($this->new, array_flip($this->keys));

        // Get current query string values and merge them with new ones

        parse_str(parse_url(htmlspecialchars_decode($currentUrl), PHP_URL_QUERY), $args);

        $queryParams = array_merge($args, $new);

        // If a query string value matches the default value, remove it from the URL

        foreach ($queryParams as $key => $val) {
            if (isset($this->defaults[$key]) && $val == $this->defaults[$key]) {
                unset($queryParams[$key]);
            }
        }

        $queryParams = array_filter($queryParams);

        return $queryParams;
    }
}
