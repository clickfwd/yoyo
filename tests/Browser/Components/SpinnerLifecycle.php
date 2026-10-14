<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class SpinnerLifecycle extends Component
{
    public $scope = 'parent';

    public $revision = 0;

    protected $props = ['scope', 'revision'];

    public function skip()
    {
        usleep(350_000);
        $this->skipRender();
    }

    public function fast()
    {
        $this->skipRender();
    }

    public function update()
    {
        usleep(350_000);
        $this->revision++;
    }
}
