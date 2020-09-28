<?php

namespace Clickfwd\Yoyo;

class AnonymousComponent extends Component
{
    public function render()
    {
        $data = array_merge($this->variables, $this->request->all());

        return $this->view($this->componentName, $data);
    }
}
