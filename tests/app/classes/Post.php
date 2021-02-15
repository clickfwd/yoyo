<?php

namespace Tests;

use Tests\Comment;

class Post
{
    protected $comment;

    protected $id;

    public function __construct(Comment $comment, $id)
    {
        $this->id = $id;

        $this->comment = $comment;
    }

    public function title()
    {
        return $this->comment->title().'-'.$this->id;
    }
}
