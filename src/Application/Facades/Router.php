<?php

namespace Adept\Application\Facades;

use Adept\Route\Route;

/**
 * @method static Route add($method,$uri,$action)
 * @method static void group($prefix, callable $callback)
 *
 * @see \Adept\Route\Router
 */
class Router extends Facade
{
    protected static $binding = \Adept\Route\RouterInterface::class;
}