<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Comment;

/**
 * Public properties are populated from caller variables merged with request data,
 * before any lifecycle hook runs. A class-typed property therefore takes whatever the
 * request carries under its name -- and a request value can never be the collaborator
 * the property is declared to hold.
 */
class PropertyInjection extends Component
{
    public ?Comment $collaborator = null;

    public $label = 'default-label';

    public ?int $count = null;

    public function render()
    {
        $collaborator = $this->collaborator === null ? 'NULL' : $this->collaborator->label();

        return $this->view('lifecycle-injection', [
            'result' => "collaborator={$collaborator} label={$this->label} count={$this->count}",
        ]);
    }
}
