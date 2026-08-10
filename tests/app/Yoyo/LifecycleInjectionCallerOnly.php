<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

/**
 * A hook with no container slots at all: filtering must leave every request value
 * available to it.
 */
class LifecycleInjectionCallerOnly extends Component
{
    public $alpha = 'default-alpha';

    public $beta = 'default-beta';

    public function mount($alpha = 'default-alpha', $beta = 'default-beta')
    {
        $this->alpha = $alpha;

        $this->beta = $beta;
    }

    public function render()
    {
        return $this->view('lifecycle-injection', ['result' => "alpha={$this->alpha} beta={$this->beta}"]);
    }
}
