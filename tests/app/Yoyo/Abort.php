<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use function Yoyo\abort;

class Abort extends Component
{
    public function initialize()
    {
        abort(404, 'not found', ['foo' => 'bar']);
    }
}
