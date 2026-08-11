<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Comment;
use Tests\App\Post;

/**
 * Composite (union / intersection) parameter types.
 *
 * PHP reports these as ReflectionUnionType / ReflectionIntersectionType, neither of
 * which has isBuiltin(). Any classifier that calls isBuiltin() on the raw type fatals
 * with an Error rather than throwing a catchable exception.
 */
class CompositeTypeParams extends Component
{
    public $result = '';

    public function unionOfClassAndBuiltin(Post|int $post)
    {
        $this->result = 'union';
    }

    public function unionOfBuiltins(int|string $value)
    {
        $this->result = 'union-builtin';
    }

    public function nullableClass(?Post $post)
    {
        $this->result = 'nullable';
    }

    public function intersection(Post&Comment $both)
    {
        $this->result = 'intersection';
    }

    public function builtinsAndUntyped(int $i, string $s, bool $b, array $a, $untyped)
    {
        $this->result = 'builtins';
    }

    public function classSlot(Post $post)
    {
        $this->result = 'class';
    }

    public function render()
    {
        return $this->view('composite-type-params', ['result' => $this->result]);
    }
}
