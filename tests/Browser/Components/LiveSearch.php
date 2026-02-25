<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class LiveSearch extends Component
{
    public $q = '';

    protected $queryString = ['q'];

    protected $results = [];

    private static $data = [
        ['title' => 'PHP Basics'],
        ['title' => 'PHP Advanced'],
        ['title' => 'JavaScript Guide'],
        ['title' => 'CSS Flexbox'],
        ['title' => 'HTML Forms'],
        ['title' => 'Laravel Framework'],
        ['title' => 'Yoyo Components'],
        ['title' => 'Alpine JS'],
    ];

    protected function getResultsProperty()
    {
        if (! $this->q) {
            return [];
        }

        return array_filter(self::$data, function ($item) {
            return stripos($item['title'], $this->q) !== false;
        });
    }
}
