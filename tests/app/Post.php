<?php

namespace Tests\App;

class Post
{
    protected $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function title()
    {
        return $this->comment->title();
    }
}
