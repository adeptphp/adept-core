<?php

namespace Adept\Misc;

class Timer
{
    protected static $id;
    protected static $recordings;

    public function __construct()
    {
        static::$id = 0;
        static::$recordings = [];
    }

    public static function mark($name = null)
    {
        static::$recordings[static::$id++] = [$name ? $name : 'none' => (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 10000];
    }

    public static function results()
    {
        return static::$recordings;
    }
}
