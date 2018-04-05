<?php

namespace Adept\Config;

interface ConfigInterface
{
    public function __construct($config = null);

    public static function get(String $key = null, $default = null);
}
