<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

use Tests\App\Post;

class DependencyInjectionClassWithNamedArgumentMapping extends Component
{
    protected $id;

    protected $post;

    // $foo variable passed to component is automaticaly injected in Post::__constructor
    // using dependency injection
    public function mount(Post $post, $id)
    {
        $this->id = $id;

        $this->post = $post;
    }

    public function getOutputProperty()
    {
        return $this->post->title().'-'.$this->id;
    }
}
