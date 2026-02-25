<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class MultiScreen extends Component
{
    public $message = '';

    public function open()
    {
        // Renders the form screen via actionMatches('open')
    }

    public function submit()
    {
        // Renders the success screen via actionMatches('submit')
    }
}
