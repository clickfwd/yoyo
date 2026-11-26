<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Tests\App\Comment;

#[\AllowDynamicProperties]
class InputPresence extends Component
{
    public ?string $label = 'default-label';

    public ?Comment $collaborator;

    public $result = '';

    protected $listeners = [
        'optional-event' => 'onOptional',
        'nullable-event' => 'onRequiredNullable',
        'variadic-event' => 'onOptionalVariadic',
    ];

    public function __construct(ComponentResolver $resolver, string $id, string $name)
    {
        parent::__construct($resolver, $id, $name);
        $this->collaborator = new Comment();
    }

    public function optional(?string $value = 'fallback')
    {
        $this->result = json_encode($value);
    }

    public function typedOptional(string $value = 'fallback')
    {
        $this->result = $value;
    }

    public function requiredNullable(?string $value)
    {
        $this->result = json_encode($value);
    }

    public function optionalVariadic($required, ?string $optional = 'fallback', ...$rest)
    {
        $this->result = json_encode([$required, $optional, $rest]);
    }

    public function onlyOptionalVariadic(string $optional = 'fallback', ...$rest)
    {
        $this->result = json_encode([$optional, $rest]);
    }

    public function onOptional(?string $value = 'fallback')
    {
        $this->optional($value);
    }

    public function onRequiredNullable(?string $value)
    {
        $this->requiredNullable($value);
    }

    public function onOptionalVariadic($required, ?string $optional = 'fallback', ...$rest)
    {
        $this->optionalVariadic($required, $optional, ...$rest);
    }

    public function getDynamicProperties()
    {
        return ['dynamic'];
    }

    public function changeDynamic()
    {
        $this->dynamic = 'changed';
    }

    public function render()
    {
        return $this->view('input-presence');
    }
}
