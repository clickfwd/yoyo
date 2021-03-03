<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithTrait extends Component
{
    use WithFramework;

    public $output;

    public function mount()
    {
        $this->output = 'Component saw that ';
    }
}

trait WithFramework
{
    public function mountWithFramework()
    {
        $this->output .= '{mountWithFramework} was here';
    }

    public function renderedWithFramework($view)
    {
        return str_replace("Component", "{ComponentWithTrait}", $view);
    }
}
