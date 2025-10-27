<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Comment;
use Tests\App\Post;

class DependencyInjectionAction extends Component
{
    public $result = '';

    /**
     * Test action with only typed parameters (dependency injection)
     */
    public function onlyTyped(Post $post)
    {
        $this->result = 'Post title: ' . $post->title();
    }

    /**
     * Test action with multiple typed parameters
     */
    public function multipleTyped(Post $post, Comment $comment)
    {
        $this->result = 'Post: ' . $post->title() . ', Comment: ' . $comment->body();
    }

    /**
     * Test action with mixed typed and regular parameters
     */
    public function mixedTypedAndRegular(Post $post, $id, $status = 'active')
    {
        $this->result = "Post: {$post->title()}, ID: {$id}, Status: {$status}";
    }

    /**
     * Test action with typed and variadic parameters
     */
    public function typedWithVariadic(Post $post, ...$tags)
    {
        $this->result = "Post: {$post->title()}, Tags: " . json_encode($tags);
    }

    /**
     * Test action with typed and optional regular parameter
     */
    public function typedWithOptional(Post $post, ?string $status = null)
    {
        $statusText = $status ?? 'default';
        $this->result = "Post: {$post->title()}, Status: {$statusText}";
    }

    public function render()
    {
        return $this->view('dependency-injection-action', ['result' => $this->result]);
    }
}
