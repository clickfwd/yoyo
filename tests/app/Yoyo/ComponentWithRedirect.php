<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithRedirect extends Component
{
    public function save()
    {
        $this->redirect('/success');
    }
}
