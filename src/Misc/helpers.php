<?php

use Adept\Application\Application;
use Adept\Config\ConfigInterface;
use Adept\Route\RouterInterface;
use Adept\View\ViewInterface;
use Adept\Log\LoggerInterface;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Adept\Misc\HtmlDumper;

if (!function_exists('strBefore')) {
    /**
     * @param $string
     * @param $substring
     * @return bool|string
     */
    function strBefore($string, $substring)
    {
        $pos = strpos($string, $substring);
        if ($pos === false) {
            return $string;
        } else {
            return(substr($string, 0, $pos));
        }
    }
}

if (!function_exists('strAfter')) {
    /**
     * @param $string
     * @param $substring
     * @return bool|string
     */
    function strAfter($string, $substring)
    {
        $pos = strpos($string, $substring);
        if ($pos === false) {
            return $string;
        } else {
            return(substr($string, $pos + strlen($substring)));
        }
    }
}

if (!function_exists('app')) {
    /**
     * @param null $make
     * @param array $parameters
     * @return \Jshannon63\Cobalt\Container|object
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return Application::getContainer();
        } elseif (Application::getContainer()->has($make)) {
            return Application::getContainer()[$make];
        } elseif (Application::getContainer()->alias($make)) {
            return Application::getContainer()->$make();
        } else {
            return Application::getContainer()->make($make, $parameters);
        }
    }
}

if (!function_exists('env')) {
    /**
     * @param $key
     * @param null $default
     * @return array|bool|false|mixed|string|void
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && $value[0] == '"' && $value[strlen($value) - 1] == '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * @param null $key
     * @param null $default
     * @return \Jshannon63\Cobalt\Container|object
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        return app(ConfigInterface::class)->get($key, $default);
    }
}

if (!function_exists('value')) {

    /**
     * @param $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and then die.
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        $args = func_get_args();
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        array_map(function ($x) use ($caller) {
            if (class_exists(CliDumper::class)) {
                $dumper = 'cli' === PHP_SAPI ? new CliDumper : new HtmlDumper;
                if ($dumper instanceof HtmlDumper) {
                    $dumper->prependHeader('<span class="sf-dump-note">Dumped and Died from: '.$caller['file'].':'.$caller['line'].'<hr></span>');
                } else {
                    echo 'Dumped and Died from: '.$caller['file'].':'.$caller['line']."\n";
                }
                $dumper->dump((new VarCloner)->cloneVar($x));
            } else {
                var_dump($x);
            }
        }, $args);
        die(1);
    }
}

if (!function_exists('d')) {
    /**
     * Dump and continue
     *
     * @param  mixed
     * @return void
     */
    function d()
    {
        $args = func_get_args();
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        array_map(function ($x) use ($caller) {
            if (class_exists(CliDumper::class)) {
                $dumper = 'cli' === PHP_SAPI ? new CliDumper : new HtmlDumper;
                if ($dumper instanceof HtmlDumper) {
                    $dumper->prependHeader('<span class="sf-dump-note">Dumped from: '.$caller['file'].':'.$caller['line'].'<hr></span>');
                } else {
                    echo 'Dumped from: '.$caller['file'].':'.$caller['line']."\n";
                }
                $dumper->dump((new VarCloner)->cloneVar($x));
            } else {
                var_dump($x);
            }
        }, $args);
    }
}

if (!function_exists('loginfo')) {
    /**
     * @param $message
     * @param array $context
     * @return mixed
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    function loginfo($message, array $context = [])
    {
        return app(LoggerInterface::class)->info($message, $context);
    }
}

if (!function_exists('view')) {
    /**
     * @param $template
     * @param null $variables
     * @return mixed
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    function view($template, $variables = null)
    {
        return app(ViewInterface::class)->render($template, $variables);
    }
}

if (!function_exists('route')) {
    /**
     * @param $method
     * @param $uri
     * @param $action
     * @return mixed
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    function route($method, $uri, $action)
    {
        return app(RouterInterface::class)->add($method, $uri, $action);
    }
}

if (!function_exists('group')) {
    /**
     * @param $prefix
     * @param callable $callback
     * @return mixed
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    function group($prefix, callable $callback)
    {
        return app(RouterInterface::class)->group($prefix, $callback);
    }
}

if (! function_exists('str_slug')) {
    /**
     * @param $title
     * @param string $separator
     * @return string
     */
    function str_slug($title, $separator = '-')
    {
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';

        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

        // Replace @ with the word 'at'
        $title = str_replace('@', $separator.'at'.$separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

        return trim($title, $separator);    }
}

if (! function_exists('getClassFiles')) {
    function getClassFiles($directory): array
    {
        if (! is_dir($directory)) {
            return null;
        }
        foreach (new DirectoryIterator($directory) as $count => $fileInfo) {
            if (! $fileInfo->isDot()) {
                $files[$count]['pathname'] = $fileInfo->getPathname();
                $files[$count]['classname'] = getClassFromFile($fileInfo->getPathname());
                $files[$count]['basename'] = basename(str_replace('\\', '/', $files[$count]['classname']));
            }
        }
        return $files;
    }
}

if (! function_exists('getClassFromFile')) {
    function getClassFromFile($path_to_file)
    {
        $contents = file_get_contents($path_to_file);
        $namespace = $class = "";
        $getting_namespace = $getting_class = false;
        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $getting_namespace = true;
            }
            if (is_array($token) && $token[0] == T_CLASS) {
                $getting_class = true;
            }
            if ($getting_namespace === true) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    $namespace .= $token[1];
                } else {
                    if ($token === ';') {
                        $getting_namespace = false;
                    }
                }
            }
            if ($getting_class === true) {
                if (is_array($token) && $token[0] == T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }
        return $namespace ? $namespace.'\\'.$class : $class;
    }
}