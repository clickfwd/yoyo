<?php

namespace Clickfwd\Yoyo;

class YoyoHelpers
{
    public static function encode_vals(array $vars): string
    {
        $adjusted = [];
        foreach ($vars as $key => $val) {
            $newKey = is_array($val) ? $key.'[]' : $key;
            $adjusted[$newKey] = $val;
        }

        return json_encode($adjusted, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    public static function decode_vals(string $string): array
    {
        if (empty($string)) {
            return [];
        }

        return json_decode((string) $string, true);
    }

    public static function decode_val(string $string)
    {
        if ($json = self::test_json($string)) {
            return $json;
        }

        return $string === '0' ? 0 : $string;
    }

    public static function test_json($string)
    {
        if (is_array($string)) {
            return $string;
        }

        if (! is_string($string)) {
            return null;
        }

        $decoded = json_decode(stripslashes($string), true);

        return $decoded ?? null;
    }

    public static function studly($str, $delimiter = ['-', '_'])
    {
        $str = str_replace(' ', '', ucwords(str_replace($delimiter, ' ', $str)));

        return $str;
    }

    public static function camel($str, $delimiter = ['-', '_'])
    {
        return lcfirst(static::studly($str, $delimiter));
    }

    public static function snake($str, $delimiter = '_')
    {
        if (! ctype_lower($str)) {
            $str = preg_replace('/\\s+/u', '', ucwords($str));
            $str = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $str), 'UTF-8');
        }

        return $str;
    }

    public static function randString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function removeEmptyValues(array &$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = static::removeEmptyValues($value);
            }

            if (is_array($value) && empty($value)) {
                unset($array[$key]);
            }

            if (is_null($value) || (is_string($value) && ! strlen($value))) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
