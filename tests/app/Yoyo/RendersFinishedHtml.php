<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class RendersFinishedHtml extends Component
{
    public $title = '';

    public function render()
    {
        return '<div>'.htmlspecialchars((string) $this->title).'</div>';
    }
}
