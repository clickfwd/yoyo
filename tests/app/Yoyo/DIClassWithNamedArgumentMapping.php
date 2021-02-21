<?php

namespace Tests\App\Yoyo;

use Tests\App\Post;

use Clickfwd\Yoyo\Component;

class DIClassWithNamedArgumentMapping extends Component
{
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
