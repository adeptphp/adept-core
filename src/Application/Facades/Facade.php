<?php

namespace Adept\Application\Facades;

use Adept\Application\Application;

class Facade{
    protected static $binding;

    public static function __callStatic($name, $arguments)
    {
        if(Application::getContainer()->has(static::$binding)){
            return call_user_func_array([Application::getContainer()[static::$binding], $name], $arguments);
        }
    }
}