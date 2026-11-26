<?php

namespace Tests\App\Yoyo;

class ActionAccess extends Counter
{
    public $renderCalls = 0;

    protected $listeners = [
        'CountChanged' => 'INCREMENT',
        'redraw' => 'RENDER',
        'refresh',
        'reload' => 'refresh',
        'internal-helper' => 'GETNAME',
    ];

    public function getNAME()
    {
        return parent::getName();
    }

    public function render()
    {
        $this->renderCalls++;

        return $this->view('action-access');
    }
}
