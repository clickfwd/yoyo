<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Post;

/**
 * A nullable container slot. Request values decode to real PHP types, so a literal
 * "null" in the query string becomes null -- which satisfies ?Post without any
 * TypeError and silently displaces the container's object.
 */
class LifecycleInjectionNullable extends Component
{
    protected $postTitle = 'none';

    public function mount(?Post $post)
    {
        $this->postTitle = $post === null ? 'NULL-INJECTED' : $post->label();
    }

    public function render()
    {
        return $this->view('lifecycle-injection', ['result' => "post={$this->postTitle}"]);
    }
}
