<?php

namespace Adept\Application\Facades;

use Adept\Route\Route;

/**
 * @method static array dispatch($method, $uri))
 * @method static Route add($method,$uri,$action)
 * @method static void group($prefix, callable $callback)
 * @method static Route middleware(array $middlewares)
 * @method static Route redirect($method, $original, $redirect, $code)
 * @method static Route get($uri, $action)
 * @method static Route put($uri, $action)
 * @method static Route post($uri, $action)
 * @method static Route delete($uri, $action)
 * @method static Route patch($uri, $action)
 * @method static Route head($uri, $action)
 *
 * @see \Adept\Route\Router
 */
class Router extends Facade
{
    protected static $binding = \Adept\Route\RouterInterface::class;
}