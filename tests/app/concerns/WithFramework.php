<?php

namespace Tests\App\Concerns;

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
