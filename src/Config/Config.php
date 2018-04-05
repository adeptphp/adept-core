<?php

namespace Adept\Config;

class Config implements ConfigInterface
{
    protected static $config;

    public function __construct($config = null)
    {
        switch ($config) {
            case is_array($config):
                self::$config[] = $config;
                break;
            case is_file($config):
                self::$config[] = require $config;
                break;
            case is_dir($config):
                foreach (new \DirectoryIterator($config) as $fileInfo) {
                    if (!$fileInfo->isDot()) {
                        self::$config[strtolower($fileInfo->getBasename('.php'))] = require $fileInfo->getPathname();
                    }
                }
                break;
            default:
                break;
        }
    }

    public static function get(String $key = null, $default = null)
    {
        $config = self::$config;

        if(is_null($key)){
            return $config;
        }

        if (isset($config[$key])) {
            return $config[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return ($default instanceof Closure ? $default() : $default);
            }
            $config = $config[$segment];
        }

        return $config;
    }
}
