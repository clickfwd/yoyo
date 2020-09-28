<?php

namespace Clickfwd\Yoyo\Twig;

use Twig\Markup;

class YoyoVariable
{
    public function getScripts()
    {
        return self::raw(yoyo_scripts());
    }

    public function getCall($closure, ...$params)
    {
        return  call_user_func_array($closure, $params);
    }

    private static function raw($string)
    {
        return new Markup($string, 'UTF-8');
    }
}
