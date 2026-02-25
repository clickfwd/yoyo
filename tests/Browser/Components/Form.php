<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class Form extends Component
{
    public $name = '';

    public $email = '';

    public $success = false;

    public $errors = [];

    public function register()
    {
        $this->errors = [];

        if (empty($this->name)) {
            $this->errors['name'] = 'Name is required.';
        }

        if (empty($this->email)) {
            $this->errors['email'] = 'Email is required.';
        }

        if (empty($this->errors)) {
            $this->success = true;
        }
    }
}
