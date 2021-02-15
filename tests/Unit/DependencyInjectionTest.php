<?php

use Clickfwd\Yoyo\DI;

class Post
{
    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }
    public function title()
    {
        return 'post title'.$this->id;
    }
}

class DI1
{
    protected $out;
    protected $post;

    public function __construct($id = null)
    {
        $this->out = $id;
    }
    
    public function post(Post $post)
    {
        $this->post = $post;
        return $this;
    }
    
    public function many(Post $post, $foo, $baz)
    {
        $this->out = $post->title().$foo.$baz;
        return $this;
    }

    public function out1()
    {
        return $this->out;
    }

    public function out2()
    {
        return $this->post->title();
    }
}

test('di with non-class arguments', function () {
    $di = DI::call(DI1::class, ['id' => 100]);
    expect($di->out1())->toBe(100);
});

test('di with class arguments and custom method', function () {
    $di = DI::call(DI1::class, [], 'post');
    expect($di->out2())->toBe('post title');
});

test('di with class arguments and recursive mapping', function () {
    $di = DI::call(DI1::class, ['id'=>'100'], 'post');
    expect($di->out2())->toBe('post title100');
});

test('di with class and non-class arguments, mapping, and recursive mapping', function () {
    $di = DI::call(DI1::class, ['id'=>'200', 'foo' => 'bar', 'baz' => 'qux'], 'many');
    expect($di->out1())->toBe('post title200barqux');
});

test('di with instantiated class and non-class arguments, mapping, and recursive mapping', function () {
    $di = new DI1();
    DI::call($di, ['id'=>'300', 'foo' => 'bar', 'baz' => 'qux'], 'many');
    expect($di->out1())->toBe('post title300barqux');
});
