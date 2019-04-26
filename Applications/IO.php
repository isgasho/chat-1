<?php

namespace App;


class IO
{
    public static function encode($type, $data)
    {
        return json_encode(compact('type', 'data'));
    }

    public static function decode($json)
    {
        $array = json_decode($json, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            return [];
        }

        return $array;
    }
}
