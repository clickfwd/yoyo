<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Comment;
use Tests\App\Post;

/**
 * Mirrors the dominant real-world shape: lifecycle hooks that take a container-built
 * collaborator alongside caller-supplied scalars.
 *
 * $comment and $post are container slots; $id is caller-supplied. A request variable
 * sharing a container slot's name must not displace the container's object, while a
 * request variable matching $id must still fill it.
 */
class LifecycleInjection extends Component
{
    public $id = 0;

    protected $commentTitle = 'none';

    protected $postTitle = 'none';

    public function initialize(Comment $comment)
    {
        $this->commentTitle = $comment->label();
    }

    public function mount(Post $post, $id = 0)
    {
        $this->postTitle = $post->label();

        $this->id = $id;
    }

    public function render()
    {
        return $this->view('lifecycle-injection', [
            'result' => "comment={$this->commentTitle} post={$this->postTitle} id={$this->id}",
        ]);
    }
}
