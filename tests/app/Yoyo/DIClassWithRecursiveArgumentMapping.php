<?php

namespace Tests\App\Yoyo;

use Tests\Post;

use Clickfwd\Yoyo\Component;

class DIClassWithRecursiveArgumentMapping extends Component
{
    protected $post;

    // $foo variable passed to component is automaticaly injected in Post::__constructor
    // using dependency injection
    public function mount(Post $post)
    {
        $this->post = $post;
    }

    public function getOutputProperty()
    {
        return $this->post->title();
    }
}
