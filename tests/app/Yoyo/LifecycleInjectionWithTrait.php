<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Comment;

/**
 * Trait lifecycle hooks run through the same hook stack as the component's own, so
 * container slots must be resolved per-method rather than per-component.
 */
class LifecycleInjectionWithTrait extends Component
{
    use WithLifecycleHook;
    protected $traitComment = 'none';

    public function render()
    {
        return $this->view('lifecycle-injection', ['result' => "comment={$this->traitComment}"]);
    }
}

trait WithLifecycleHook
{
    public function mountWithLifecycleHook(Comment $comment)
    {
        $this->traitComment = $comment->label();
    }
}
