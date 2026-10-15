<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Tests\App\Comment;

class TypedInputPresence extends Component
{
    public bool $open = true;

    public int $limit = 15;

    public string $title = 'default-title';

    public string $current = 'default-current';

    public int|string $selection = 25;

    public int|string|null $nullableSelection = 25;

    public mixed $mixedValue = 'default-mixed';

    public $untypedValue = 'default-untyped';

    public Comment $collaborator;

    public function __construct(ComponentResolver $resolver, string $id, string $name)
    {
        parent::__construct($resolver, $id, $name);
        $this->current = 'constructor-current';
        $this->collaborator = new Comment();
    }

    public function render()
    {
        return $this->view('typed-input-presence');
    }
}
