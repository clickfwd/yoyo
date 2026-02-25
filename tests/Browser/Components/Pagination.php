<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class Pagination extends Component
{
    public $page = 1;

    protected $queryString = ['page'];

    protected $props = ['page'];

    private $perPage = 3;

    private $totalItems = 12;

    protected function getResultsProperty()
    {
        $items = [];

        for ($i = 1; $i <= $this->totalItems; $i++) {
            $items[] = ['title' => "Item $i"];
        }

        $offset = ($this->page - 1) * $this->perPage;

        return array_slice($items, $offset, $this->perPage);
    }

    protected function getTotalPagesProperty()
    {
        return (int) ceil($this->totalItems / $this->perPage);
    }

    protected function getStartProperty()
    {
        return (($this->page - 1) * $this->perPage) + 1;
    }

    protected function getEndProperty()
    {
        return min($this->page * $this->perPage, $this->totalItems);
    }
}
