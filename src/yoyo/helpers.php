<?php

if (! function_exists('yoyo_render')) {
    function yoyo_render($name, $variables = [], $attributes = []): string
    {
        $yoyo = new Clickfwd\Yoyo\Yoyo();

        return $yoyo->mount($name, $variables, $attributes)->render();
    }
}

if (! function_exists('yoyo_scripts')) {
    function yoyo_scripts($return = false)
    {
        $output = Clickfwd\Yoyo\Services\Configuration::scripts();
        if ($return) {
            return $output;
        }
        echo $output;
    }
}

if (! function_exists('yoyo_styles')) {
    function yoyo_styles($return = false)
    {
        $output = Clickfwd\Yoyo\Services\Configuration::styles();
        if ($return) {
            return $output;
        }
        echo $output;
    }
}

if (! function_exists('is_spinning')) {
    function is_spinning($expression = null)
    {
        if (Clickfwd\Yoyo\Yoyo::is_spinning()) {
            if (! $expression) {
                return true;
            }

            echo $expression;
        } elseif (! $expression) {
            return false;
        }
    }
}

if (! function_exists('not_spinning')) {
    function not_spinning($expression = null)
    {
        if (! Clickfwd\Yoyo\Yoyo::is_spinning()) {
            if (! $expression) {
                return true;
            }

            echo $expression;
        } elseif (! $expression) {
            return false;
        }
    }
}

if (! function_exists('d')) {
    function d(...$params)
    {
        var_dump(...$params);
    }
}

if (! function_exists('dd')) {
    function dd(...$params)
    {
        d(...$params);

        exit;
    }
}

if (! function_exists('prx')) {
    function prx(...$params)
    {
        foreach ($params as $param) {
            echo '<pre>';
            print_r($param);
            echo '</pre>';
        }
    }
}

if (! function_exists('cm')) {
    function cm($class)
    {
        dd(get_class_methods($class));
    }
}
