<?php

namespace Clickfwd\Yoyo;

class YoyoHelpers
{
    /**
     * $expresionKeys allows the output of values as javascript expression, without quotes.
     */
    public static function encode_vars(array $vars, array $expressionKeys = []): string
    {
        $output = [];

        foreach ($vars as $key => $val) {
            if (in_array($key, $expressionKeys) || is_numeric($val)) {
                $output[] = "'$key':$val";
            } elseif (is_array($val)) {
                $output[] = "'$key':'".json_encode($val)."'";
            } else {
                $output[] = "'$key':'$val'";
            }
        }

        return implode(',', $output);
    }

    public static function decode_vars($string): array
    {
        if (empty($string)) {
            return [];
        }

        $vars = [];

        foreach (explode(',', $string) as $var) {
            [$key, $value] = explode(':', $var);

            $key = ltrim(rtrim($key, "'"), "'");

            if ($decoded = self::test_json($value[0])) {
                $vars[$key] = $decoded;
            } else {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    public static function test_json($string)
    {
        if (is_array($string)) {
            return $string;
        }

        $decoded = json_decode($string, true);

        return $decoded ?? null;
    }

    public static function studly($str)
    {
        $str = str_replace('-', '', ucwords($str, '-'));

        return $str;
    }

    public static function randString($length = 6)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
